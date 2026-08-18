#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT="${FLOWERFLOW_TEST_PORT:-8017}"

if [[ ! "$PORT" =~ ^[0-9]+$ ]] || ((PORT < 1024 || PORT > 65535)); then
  printf 'Error: FLOWERFLOW_TEST_PORT debe ser un puerto no privilegiado entre 1024 y 65535.\n' >&2
  exit 1
fi

cd "$ROOT"

export APP_ENV=testing
export APP_URL="http://127.0.0.1:${PORT}"
export DB_CONNECTION=mysql
export DB_HOST=127.0.0.1
export DB_DATABASE=flowerflow_testing
export DB_USERNAME=flowerflow_testing_user
export SESSION_DRIVER=file
export CACHE_STORE=file
export MAIL_MAILER=array
export QUEUE_CONNECTION=sync
export FLOWERFLOW_REGISTRATION_ENABLED=true
export FLOWERFLOW_SUBMISSIONS_ENABLED=true
export FLOWERFLOW_PANEL_ENABLED=true
export FLOWERFLOW_ADMISSIBILITY_REVIEW_ENABLED=true
export FLOWERFLOW_RESULTS_ENABLED=false
export FLOWERFLOW_SUBMISSIONS_CLOSE_AT="${FLOWERFLOW_SUBMISSIONS_CLOSE_AT:-2026-08-23T23:59:59-07:00}"

php artisan tinker --execute='
$connection = Illuminate\Support\Facades\DB::connection();
$facts = [
    "app_env" => app()->environment(),
    "driver" => config("database.default"),
    "host" => config("database.connections.mysql.host"),
    "database" => config("database.connections.mysql.database"),
    "username" => config("database.connections.mysql.username"),
    "selected_database" => $connection->selectOne("SELECT DATABASE() AS db")->db,
];

if (
    $facts["app_env"] !== "testing"
    || $facts["driver"] !== "mysql"
    || ! in_array($facts["host"], ["127.0.0.1", "localhost"], true)
    || $facts["database"] !== "flowerflow_testing"
    || $facts["username"] !== "flowerflow_testing_user"
    || $facts["selected_database"] !== "flowerflow_testing"
) {
    fwrite(STDERR, "TEST_DB_GUARD_FAILED\n");
    exit(70);
}

echo json_encode($facts, JSON_PRETTY_PRINT), PHP_EOL;
'

if [[ "${FLOWERFLOW_TEST_GUARD_ONLY:-false}" == "true" ]]; then
  exit 0
fi

php artisan tinker --execute='
$requiredTables = ["roles", "permissions", "competitions", "categories", "legal_documents"];
$missingTables = array_values(array_filter(
    $requiredTables,
    static fn (string $table): bool => ! Illuminate\Support\Facades\Schema::hasTable($table),
));

if ($missingTables !== []) {
    fwrite(STDERR, "TEST_RUNTIME_SCHEMA_NOT_READY: run migrations and seeders first\n");
    exit(71);
}

$db = Illuminate\Support\Facades\DB::connection();
$facts = [
    "participant_role" => $db->table("roles")->where("name", "participant")->where("guard_name", "web")->count(),
    "active_competitions" => $db->table("competitions")->where("slug", "hermosillo-florece-2026")->where("active", true)->count(),
    "active_categories" => $db->table("categories")->where("active", true)->count(),
    "active_legal_types" => $db->table("legal_documents")
        ->where("active", true)
        ->whereIn("code", ["mechanics", "terms", "privacy"])
        ->distinct()
        ->count("code"),
    "registration_enabled" => config("flowerflow.flags.registration"),
    "submissions_enabled" => config("flowerflow.flags.submissions"),
    "panel_enabled" => config("flowerflow.flags.panel"),
    "admissibility_enabled" => config("flowerflow.flags.admissibility_review"),
    "results_enabled" => config("flowerflow.flags.results"),
];

if (
    $facts["participant_role"] !== 1
    || $facts["active_competitions"] !== 1
    || $facts["active_categories"] !== 4
    || $facts["active_legal_types"] !== 3
    || $facts["registration_enabled"] !== true
    || $facts["submissions_enabled"] !== true
    || $facts["panel_enabled"] !== true
    || $facts["admissibility_enabled"] !== true
    || $facts["results_enabled"] !== false
) {
    fwrite(STDERR, "TEST_RUNTIME_DATA_NOT_READY: run the approved seeders and verify feature flags\n");
    exit(72);
}

echo json_encode($facts, JSON_PRETTY_PRINT), PHP_EOL;
'

exec php artisan serve --host=127.0.0.1 --port="$PORT"
