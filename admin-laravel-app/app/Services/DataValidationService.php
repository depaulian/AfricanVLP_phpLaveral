<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class DataValidationService
{
    /**
     * Validate data integrity after migration
     */
    public function validateDataIntegrity(): array
    {
        $results = [
            'overall_status' => 'pending',
            'total_checks' => 0,
            'passed_checks' => 0,
            'failed_checks' => 0,
            'warnings' => 0,
            'checks' => [],
            'summary' => [],
        ];

        $checks = [
            'validateTableStructures',
            'validateForeignKeyConstraints',
            'validateDataConsistency',
            'validateRequiredFields',
            'validateDataTypes',
            'validateUniqueConstraints',
            'validateRelationshipIntegrity',
            'validateTimestamps',
            'validateEmailFormats',
            'validateJsonFields',
        ];

        foreach ($checks as $check) {
            try {
                $checkResult = $this->$check();
                $results['checks'][$check] = $checkResult;
                $results['total_checks']++;

                if ($checkResult['status'] === 'passed') {
                    $results['passed_checks']++;
                } elseif ($checkResult['status'] === 'failed') {
                    $results['failed_checks']++;
                } elseif ($checkResult['status'] === 'warning') {
                    $results['warnings']++;
                }
            } catch (\Exception $e) {
                $results['checks'][$check] = [
                    'status' => 'error',
                    'message' => 'Check failed to execute: ' . $e->getMessage(),
                    'details' => [],
                ];
                $results['failed_checks']++;
                $results['total_checks']++;
            }
        }

        // Determine overall status
        if ($results['failed_checks'] > 0) {
            $results['overall_status'] = 'failed';
        } elseif ($results['warnings'] > 0) {
            $results['overall_status'] = 'warning';
        } else {
            $results['overall_status'] = 'passed';
        }

        // Create summary
        $results['summary'] = $this->createValidationSummary($results);

        return $results;
    }

    /**
     * Validate table structures exist
     */
    protected function validateTableStructures(): array
    {
        $requiredTables = [
            'users', 'organizations', 'events', 'resources', 'resource_files',
            'news', 'blog_posts', 'notifications', 'messages', 'forums',
            'forum_posts', 'organization_user', 'tmp_organization_users',
            'event_participants', 'category_of_resources', 'resource_types',
        ];

        $missingTables = [];
        $existingTables = [];

        foreach ($requiredTables as $table) {
            if (Schema::hasTable($table)) {
                $existingTables[] = $table;
            } else {
                $missingTables[] = $table;
            }
        }

        return [
            'status' => empty($missingTables) ? 'passed' : 'failed',
            'message' => empty($missingTables) 
                ? 'All required tables exist' 
                : 'Missing tables: ' . implode(', ', $missingTables),
            'details' => [
                'existing_tables' => $existingTables,
                'missing_tables' => $missingTables,
                'total_required' => count($requiredTables),
                'total_existing' => count($existingTables),
            ],
        ];
    }

    /**
     * Validate foreign key constraints
     */
    protected function validateForeignKeyConstraints(): array
    {
        $constraints = [
            'resources' => [
                ['column' => 'organization_id', 'references' => 'organizations.id'],
                ['column' => 'created_by', 'references' => 'users.id'],
            ],
            'events' => [
                ['column' => 'organization_id', 'references' => 'organizations.id'],
                ['column' => 'created_by', 'references' => 'users.id'],
            ],
            'notifications' => [
                ['column' => 'user_id', 'references' => 'users.id'],
            ],
            'messages' => [
                ['column' => 'sender_id', 'references' => 'users.id'],
                ['column' => 'recipient_id', 'references' => 'users.id'],
            ],
            'organization_user' => [
                ['column' => 'user_id', 'references' => 'users.id'],
                ['column' => 'organization_id', 'references' => 'organizations.id'],
            ],
        ];

        $violations = [];
        $validConstraints = 0;

        foreach ($constraints as $table => $tableConstraints) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($tableConstraints as $constraint) {
                $column = $constraint['column'];
                $reference = explode('.', $constraint['references']);
                $refTable = $reference[0];
                $refColumn = $reference[1];

                // Check for orphaned records
                $orphanedCount = DB::table($table)
                    ->whereNotNull($column)
                    ->whereNotExists(function ($query) use ($refTable, $refColumn, $column) {
                        $query->select(DB::raw(1))
                              ->from($refTable)
                              ->whereColumn("{$refTable}.{$refColumn}", "=", $column);
                    })
                    ->count();

                if ($orphanedCount > 0) {
                    $violations[] = [
                        'table' => $table,
                        'column' => $column,
                        'references' => $constraint['references'],
                        'orphaned_records' => $orphanedCount,
                    ];
                } else {
                    $validConstraints++;
                }
            }
        }

        return [
            'status' => empty($violations) ? 'passed' : 'failed',
            'message' => empty($violations) 
                ? 'All foreign key constraints are valid' 
                : 'Found ' . count($violations) . ' foreign key violations',
            'details' => [
                'violations' => $violations,
                'valid_constraints' => $validConstraints,
                'total_constraints' => $validConstraints + count($violations),
            ],
        ];
    }

    /**
     * Validate data consistency
     */
    protected function validateDataConsistency(): array
    {
        $issues = [];

        // Check for duplicate emails in users table
        if (Schema::hasTable('users')) {
            $duplicateEmails = DB::table('users')
                ->select('email', DB::raw('COUNT(*) as count'))
                ->groupBy('email')
                ->having('count', '>', 1)
                ->get();

            if ($duplicateEmails->isNotEmpty()) {
                $issues[] = [
                    'type' => 'duplicate_emails',
                    'table' => 'users',
                    'count' => $duplicateEmails->count(),
                    'details' => $duplicateEmails->toArray(),
                ];
            }
        }

        // Check for organizations without names
        if (Schema::hasTable('organizations')) {
            $orgsWithoutNames = DB::table('organizations')
                ->whereNull('name')
                ->orWhere('name', '')
                ->count();

            if ($orgsWithoutNames > 0) {
                $issues[] = [
                    'type' => 'missing_organization_names',
                    'table' => 'organizations',
                    'count' => $orgsWithoutNames,
                ];
            }
        }

        // Check for events with invalid dates
        if (Schema::hasTable('events')) {
            $invalidDateEvents = DB::table('events')
                ->where('start_date', '>', 'end_date')
                ->count();

            if ($invalidDateEvents > 0) {
                $issues[] = [
                    'type' => 'invalid_event_dates',
                    'table' => 'events',
                    'count' => $invalidDateEvents,
                ];
            }
        }

        return [
            'status' => empty($issues) ? 'passed' : 'warning',
            'message' => empty($issues) 
                ? 'Data consistency checks passed' 
                : 'Found ' . count($issues) . ' data consistency issues',
            'details' => [
                'issues' => $issues,
                'total_issues' => count($issues),
            ],
        ];
    }

    /**
     * Validate required fields
     */
    protected function validateRequiredFields(): array
    {
        $requiredFields = [
            'users' => ['name', 'email'],
            'organizations' => ['name'],
            'events' => ['title', 'start_date'],
            'resources' => ['title'],
            'news' => ['title', 'content'],
            'blog_posts' => ['title', 'content'],
        ];

        $violations = [];

        foreach ($requiredFields as $table => $fields) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($fields as $field) {
                $nullCount = DB::table($table)
                    ->whereNull($field)
                    ->orWhere($field, '')
                    ->count();

                if ($nullCount > 0) {
                    $violations[] = [
                        'table' => $table,
                        'field' => $field,
                        'null_count' => $nullCount,
                    ];
                }
            }
        }

        return [
            'status' => empty($violations) ? 'passed' : 'failed',
            'message' => empty($violations) 
                ? 'All required fields are populated' 
                : 'Found ' . count($violations) . ' required field violations',
            'details' => [
                'violations' => $violations,
            ],
        ];
    }

    /**
     * Validate data types
     */
    protected function validateDataTypes(): array
    {
        $issues = [];

        // Validate email formats
        if (Schema::hasTable('users')) {
            $invalidEmails = DB::table('users')
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->whereRaw("email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'")
                ->count();

            if ($invalidEmails > 0) {
                $issues[] = [
                    'type' => 'invalid_email_format',
                    'table' => 'users',
                    'count' => $invalidEmails,
                ];
            }
        }

        // Validate date formats
        $dateTables = ['events', 'news', 'blog_posts'];
        foreach ($dateTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $invalidDates = DB::table($table)
                ->whereNotNull('created_at')
                ->whereRaw("created_at = '0000-00-00 00:00:00'")
                ->count();

            if ($invalidDates > 0) {
                $issues[] = [
                    'type' => 'invalid_date_format',
                    'table' => $table,
                    'field' => 'created_at',
                    'count' => $invalidDates,
                ];
            }
        }

        return [
            'status' => empty($issues) ? 'passed' : 'warning',
            'message' => empty($issues) 
                ? 'Data type validation passed' 
                : 'Found ' . count($issues) . ' data type issues',
            'details' => [
                'issues' => $issues,
            ],
        ];
    }

    /**
     * Validate unique constraints
     */
    protected function validateUniqueConstraints(): array
    {
        $uniqueConstraints = [
            'users' => ['email'],
            'organizations' => ['name'],
        ];

        $violations = [];

        foreach ($uniqueConstraints as $table => $fields) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($fields as $field) {
                $duplicates = DB::table($table)
                    ->select($field, DB::raw('COUNT(*) as count'))
                    ->whereNotNull($field)
                    ->where($field, '!=', '')
                    ->groupBy($field)
                    ->having('count', '>', 1)
                    ->get();

                if ($duplicates->isNotEmpty()) {
                    $violations[] = [
                        'table' => $table,
                        'field' => $field,
                        'duplicate_count' => $duplicates->count(),
                        'affected_records' => $duplicates->sum('count'),
                    ];
                }
            }
        }

        return [
            'status' => empty($violations) ? 'passed' : 'failed',
            'message' => empty($violations) 
                ? 'All unique constraints are valid' 
                : 'Found ' . count($violations) . ' unique constraint violations',
            'details' => [
                'violations' => $violations,
            ],
        ];
    }

    /**
     * Validate relationship integrity
     */
    protected function validateRelationshipIntegrity(): array
    {
        $issues = [];

        // Check organization-user relationships
        if (Schema::hasTable('organization_user')) {
            $invalidRelationships = DB::table('organization_user')
                ->leftJoin('users', 'organization_user.user_id', '=', 'users.id')
                ->leftJoin('organizations', 'organization_user.organization_id', '=', 'organizations.id')
                ->whereNull('users.id')
                ->orWhereNull('organizations.id')
                ->count();

            if ($invalidRelationships > 0) {
                $issues[] = [
                    'type' => 'invalid_organization_user_relationships',
                    'count' => $invalidRelationships,
                ];
            }
        }

        // Check event participants
        if (Schema::hasTable('event_participants')) {
            $invalidParticipants = DB::table('event_participants')
                ->leftJoin('users', 'event_participants.user_id', '=', 'users.id')
                ->leftJoin('events', 'event_participants.event_id', '=', 'events.id')
                ->whereNull('users.id')
                ->orWhereNull('events.id')
                ->count();

            if ($invalidParticipants > 0) {
                $issues[] = [
                    'type' => 'invalid_event_participants',
                    'count' => $invalidParticipants,
                ];
            }
        }

        return [
            'status' => empty($issues) ? 'passed' : 'warning',
            'message' => empty($issues) 
                ? 'Relationship integrity checks passed' 
                : 'Found ' . count($issues) . ' relationship integrity issues',
            'details' => [
                'issues' => $issues,
            ],
        ];
    }

    /**
     * Validate timestamps
     */
    protected function validateTimestamps(): array
    {
        $issues = [];
        $tables = ['users', 'organizations', 'events', 'resources', 'news', 'blog_posts'];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            // Check for null timestamps
            $nullCreatedAt = DB::table($table)->whereNull('created_at')->count();
            $nullUpdatedAt = DB::table($table)->whereNull('updated_at')->count();

            if ($nullCreatedAt > 0) {
                $issues[] = [
                    'type' => 'null_created_at',
                    'table' => $table,
                    'count' => $nullCreatedAt,
                ];
            }

            if ($nullUpdatedAt > 0) {
                $issues[] = [
                    'type' => 'null_updated_at',
                    'table' => $table,
                    'count' => $nullUpdatedAt,
                ];
            }

            // Check for future timestamps
            $futureTimestamps = DB::table($table)
                ->where('created_at', '>', now())
                ->count();

            if ($futureTimestamps > 0) {
                $issues[] = [
                    'type' => 'future_timestamps',
                    'table' => $table,
                    'count' => $futureTimestamps,
                ];
            }
        }

        return [
            'status' => empty($issues) ? 'passed' : 'warning',
            'message' => empty($issues) 
                ? 'Timestamp validation passed' 
                : 'Found ' . count($issues) . ' timestamp issues',
            'details' => [
                'issues' => $issues,
            ],
        ];
    }

    /**
     * Validate email formats
     */
    protected function validateEmailFormats(): array
    {
        $tables = ['users', 'organizations'];
        $issues = [];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $invalidEmails = DB::table($table)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->whereRaw("email NOT REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'")
                ->count();

            if ($invalidEmails > 0) {
                $issues[] = [
                    'table' => $table,
                    'count' => $invalidEmails,
                ];
            }
        }

        return [
            'status' => empty($issues) ? 'passed' : 'warning',
            'message' => empty($issues) 
                ? 'Email format validation passed' 
                : 'Found invalid email formats in ' . count($issues) . ' tables',
            'details' => [
                'issues' => $issues,
            ],
        ];
    }

    /**
     * Validate JSON fields
     */
    protected function validateJsonFields(): array
    {
        $jsonFields = [
            'users' => ['preferences', 'volunteering_interests', 'skills'],
            'organizations' => ['contact_info', 'settings'],
            'events' => ['metadata'],
        ];

        $issues = [];

        foreach ($jsonFields as $table => $fields) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($fields as $field) {
                if (!Schema::hasColumn($table, $field)) {
                    continue;
                }

                $invalidJson = DB::table($table)
                    ->whereNotNull($field)
                    ->where($field, '!=', '')
                    ->whereRaw("JSON_VALID({$field}) = 0")
                    ->count();

                if ($invalidJson > 0) {
                    $issues[] = [
                        'table' => $table,
                        'field' => $field,
                        'count' => $invalidJson,
                    ];
                }
            }
        }

        return [
            'status' => empty($issues) ? 'passed' : 'warning',
            'message' => empty($issues) 
                ? 'JSON field validation passed' 
                : 'Found invalid JSON in ' . count($issues) . ' fields',
            'details' => [
                'issues' => $issues,
            ],
        ];
    }

    /**
     * Create validation summary
     */
    protected function createValidationSummary(array $results): array
    {
        $summary = [
            'status' => $results['overall_status'],
            'total_checks' => $results['total_checks'],
            'passed' => $results['passed_checks'],
            'failed' => $results['failed_checks'],
            'warnings' => $results['warnings'],
            'success_rate' => $results['total_checks'] > 0 
                ? round(($results['passed_checks'] / $results['total_checks']) * 100, 2) 
                : 0,
        ];

        // Add recommendations based on results
        $recommendations = [];

        if ($results['failed_checks'] > 0) {
            $recommendations[] = 'Address failed validation checks before proceeding to production';
        }

        if ($results['warnings'] > 0) {
            $recommendations[] = 'Review warning items and fix data quality issues';
        }

        if ($results['overall_status'] === 'passed') {
            $recommendations[] = 'Data validation passed - ready for production deployment';
        }

        $summary['recommendations'] = $recommendations;

        return $summary;
    }

    /**
     * Get table statistics
     */
    public function getTableStatistics(): array
    {
        $tables = [
            'users', 'organizations', 'events', 'resources', 'resource_files',
            'news', 'blog_posts', 'notifications', 'messages', 'forums',
            'forum_posts', 'organization_user', 'event_participants',
        ];

        $statistics = [];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $statistics[$table] = [
                    'total_records' => DB::table($table)->count(),
                    'created_today' => DB::table($table)
                        ->whereDate('created_at', today())
                        ->count(),
                    'created_this_week' => DB::table($table)
                        ->where('created_at', '>=', now()->startOfWeek())
                        ->count(),
                    'created_this_month' => DB::table($table)
                        ->where('created_at', '>=', now()->startOfMonth())
                        ->count(),
                ];
            }
        }

        return $statistics;
    }
}