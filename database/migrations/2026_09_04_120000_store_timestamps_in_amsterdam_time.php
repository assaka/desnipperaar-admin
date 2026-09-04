<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * De app draaide op UTC terwijl het bedrijf in Amsterdam staat, dus elk moment
 * dat we lieten zien liep twee uur achter in de zomer en een uur in de winter.
 * Met APP_TIMEZONE op Europe/Amsterdam klopt alles wat hierna wordt geschreven,
 * maar wat er al staat is nog UTC. Die waarden zetten we hier om.
 *
 * Postgres rekent per rij met de zomertijd die op dát moment gold, dus een
 * ophaling in januari schuift een uur op en een in juli twee. Alleen de
 * timestamp-kolommen gaan mee. Een datum zonder tijd, zoals een ophaaldag of
 * een factuurperiode, is een kalenderdag en heeft geen tijdzone.
 *
 * Veilig omdat geen enkele timestamp met de hand wordt ingevoerd: ze komen
 * allemaal uit now() of uit de database zelf, dus ze stonden zonder
 * uitzondering in UTC.
 */
return new class extends Migration
{
    private const ZONE = 'Europe/Amsterdam';

    public function up(): void
    {
        $this->shift('UTC', self::ZONE);
    }

    public function down(): void
    {
        $this->shift(self::ZONE, 'UTC');
    }

    private function shift(string $from, string $to): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->timestampColumns() as [$table, $column]) {
            DB::statement(sprintf(
                'UPDATE %s SET %s = %s AT TIME ZONE ? AT TIME ZONE ? WHERE %s IS NOT NULL',
                $this->quote($table),
                $this->quote($column),
                $this->quote($column),
                $this->quote($column),
            ), [$from, $to]);
        }
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function timestampColumns(): array
    {
        $rows = DB::select(
            'SELECT c.table_name, c.column_name
               FROM information_schema.columns c
               JOIN information_schema.tables t
                 ON t.table_schema = c.table_schema AND t.table_name = c.table_name
              WHERE c.table_schema = current_schema()
                AND t.table_type = ?
                AND c.data_type = ?
              ORDER BY c.table_name, c.column_name',
            ['BASE TABLE', 'timestamp without time zone']
        );

        return array_map(fn ($r) => [$r->table_name, $r->column_name], $rows);
    }

    /** Namen komen uit de catalog, maar quoten kost niets en sluit verrassingen uit. */
    private function quote(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
};
