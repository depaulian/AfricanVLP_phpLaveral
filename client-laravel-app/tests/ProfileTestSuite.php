<?php

namespace Tests;

use PHPUnit\Framework\TestSuite;

/**
 * Comprehensive Profile System Test Suite
 * 
 * This test suite covers all aspects of the user profile system including:
 * - Unit tests for models and relationships
 * - Unit tests for services
 * - Feature tests for profile management workflows
 * - Integration tests for registration and verification processes
 * - Browser tests for user interactions
 * - Performance tests for profile data loading
 * - Security tests for access control and privacy
 */
class ProfileTestSuite extends TestSuite
{
    public static function suite()
    {
        $suite = new self('User Profile System');

        // Unit Tests - Models
        $suite->addTestSuite(\Tests\Unit\Models\UserProfileTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserSkillTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserVolunteeringInterestTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserVolunteeringHistoryTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserDocumentTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserAlumniOrganizationTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserRegistrationStepTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserPlatformInterestTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserProfileRelationshipsTest::class);

        // Unit Tests - Services
        $suite->addTestSuite(\Tests\Unit\Services\UserProfileServiceTest::class);

        // Feature Tests
        $suite->addTestSuite(\Tests\Feature\Profile\ProfileManagementTest::class);
        $suite->addTestSuite(\Tests\Feature\Profile\SkillManagementTest::class);
        $suite->addTestSuite(\Tests\Feature\Profile\DocumentManagementTest::class);

        // Integration Tests
        $suite->addTestSuite(\Tests\Integration\Profile\RegistrationWorkflowTest::class);
        $suite->addTestSuite(\Tests\Integration\Profile\DocumentVerificationTest::class);

        // Browser Tests
        $suite->addTestSuite(\Tests\Browser\Profile\ProfileInteractionTest::class);

        // Performance Tests
        $suite->addTestSuite(\Tests\Performance\Profile\ProfilePerformanceTest::class);

        // Security Tests
        $suite->addTestSuite(\Tests\Security\Profile\ProfileSecurityTest::class);

        return $suite;
    }

    /**
     * Run specific test categories
     */
    public static function unitTests()
    {
        $suite = new self('Profile Unit Tests');
        
        $suite->addTestSuite(\Tests\Unit\Models\UserProfileTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserSkillTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserVolunteeringInterestTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserVolunteeringHistoryTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserDocumentTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserAlumniOrganizationTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserRegistrationStepTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserPlatformInterestTest::class);
        $suite->addTestSuite(\Tests\Unit\Models\UserProfileRelationshipsTest::class);
        $suite->addTestSuite(\Tests\Unit\Services\UserProfileServiceTest::class);

        return $suite;
    }

    public static function featureTests()
    {
        $suite = new self('Profile Feature Tests');
        
        $suite->addTestSuite(\Tests\Feature\Profile\ProfileManagementTest::class);
        $suite->addTestSuite(\Tests\Feature\Profile\SkillManagementTest::class);
        $suite->addTestSuite(\Tests\Feature\Profile\DocumentManagementTest::class);

        return $suite;
    }

    public static function integrationTests()
    {
        $suite = new self('Profile Integration Tests');
        
        $suite->addTestSuite(\Tests\Integration\Profile\RegistrationWorkflowTest::class);
        $suite->addTestSuite(\Tests\Integration\Profile\DocumentVerificationTest::class);

        return $suite;
    }

    public static function browserTests()
    {
        $suite = new self('Profile Browser Tests');
        
        $suite->addTestSuite(\Tests\Browser\Profile\ProfileInteractionTest::class);

        return $suite;
    }

    public static function performanceTests()
    {
        $suite = new self('Profile Performance Tests');
        
        $suite->addTestSuite(\Tests\Performance\Profile\ProfilePerformanceTest::class);

        return $suite;
    }

    public static function securityTests()
    {
        $suite = new self('Profile Security Tests');
        
        $suite->addTestSuite(\Tests\Security\Profile\ProfileSecurityTest::class);

        return $suite;
    }
}