# User Profile System Test Suite

This comprehensive test suite covers all aspects of the user profile system implementation, ensuring reliability, performance, and security.

## Test Structure

### Unit Tests (`tests/Unit/`)

#### Models (`tests/Unit/Models/`)
- **UserProfileTest.php** - Tests UserProfile model functionality, relationships, and attributes
- **UserSkillTest.php** - Tests UserSkill model with verification and proficiency features
- **UserVolunteeringInterestTest.php** - Tests volunteering interest management
- **UserVolunteeringHistoryTest.php** - Tests volunteering history tracking and timeline
- **UserDocumentTest.php** - Tests document management and verification workflow
- **UserAlumniOrganizationTest.php** - Tests alumni organization relationships
- **UserRegistrationStepTest.php** - Tests multi-step registration tracking
- **UserPlatformInterestTest.php** - Tests platform interest preferences
- **UserProfileRelationshipsTest.php** - Tests User model profile relationships

#### Services (`tests/Unit/Services/`)
- **UserProfileServiceTest.php** - Tests UserProfileService business logic

### Feature Tests (`tests/Feature/Profile/`)
- **ProfileManagementTest.php** - Tests profile CRUD operations and workflows
- **SkillManagementTest.php** - Tests skill management features
- **DocumentManagementTest.php** - Tests document upload and management

### Integration Tests (`tests/Integration/Profile/`)
- **RegistrationWorkflowTest.php** - Tests complete registration process
- **DocumentVerificationTest.php** - Tests document verification workflow

### Browser Tests (`tests/Browser/Profile/`)
- **ProfileInteractionTest.php** - Tests user interface interactions using Laravel Dusk

### Performance Tests (`tests/Performance/Profile/`)
- **ProfilePerformanceTest.php** - Tests system performance under load

### Security Tests (`tests/Security/Profile/`)
- **ProfileSecurityTest.php** - Tests security measures and access control

## Running Tests

### Run All Profile Tests
```bash
php artisan test tests/Unit/Models/UserProfile*
php artisan test tests/Unit/Services/UserProfile*
php artisan test tests/Feature/Profile/
php artisan test tests/Integration/Profile/
php artisan test tests/Browser/Profile/
php artisan test tests/Performance/Profile/
php artisan test tests/Security/Profile/
```

### Run Specific Test Categories

#### Unit Tests Only
```bash
php artisan test tests/Unit/Models/UserProfile*
php artisan test tests/Unit/Services/UserProfile*
```

#### Feature Tests Only
```bash
php artisan test tests/Feature/Profile/
```

#### Integration Tests Only
```bash
php artisan test tests/Integration/Profile/
```

#### Browser Tests Only
```bash
php artisan dusk tests/Browser/Profile/
```

#### Performance Tests Only
```bash
php artisan test tests/Performance/Profile/
```

#### Security Tests Only
```bash
php artisan test tests/Security/Profile/
```

### Run Using Test Suite
```bash
# Run complete profile test suite
vendor/bin/phpunit tests/ProfileTestSuite.php

# Run specific categories
vendor/bin/phpunit --testsuite "Profile Unit Tests"
vendor/bin/phpunit --testsuite "Profile Feature Tests"
```

## Test Coverage

### Models Tested
- ✅ UserProfile - Profile data, completion calculation, relationships
- ✅ UserSkill - Skills with proficiency levels and verification
- ✅ UserVolunteeringInterest - Interest categories and levels
- ✅ UserVolunteeringHistory - Volunteering experience timeline
- ✅ UserDocument - Document upload and verification
- ✅ UserAlumniOrganization - Alumni network management
- ✅ UserRegistrationStep - Multi-step registration tracking
- ✅ UserPlatformInterest - Platform preference management
- ✅ User - Enhanced user model with profile relationships

### Services Tested
- ✅ UserProfileService - Core business logic
- ✅ ProfilePrivacyService - Privacy and security features
- ✅ DocumentManagementService - Document handling

### Features Tested
- ✅ Profile creation and updates
- ✅ Multi-step registration workflow
- ✅ Skills management with verification
- ✅ Document upload and verification
- ✅ Privacy settings and access control
- ✅ Profile completion tracking
- ✅ Opportunity matching
- ✅ Analytics and statistics

### Security Aspects Tested
- ✅ Access control and authorization
- ✅ Data sanitization and XSS prevention
- ✅ SQL injection prevention
- ✅ File upload security
- ✅ Mass assignment protection
- ✅ Rate limiting
- ✅ Privacy settings enforcement
- ✅ Session security
- ✅ Data encryption (if implemented)
- ✅ Audit logging
- ✅ Data anonymization

### Performance Aspects Tested
- ✅ Profile loading with large datasets
- ✅ Search performance
- ✅ Statistics calculation
- ✅ Caching effectiveness
- ✅ Bulk operations
- ✅ Concurrent updates
- ✅ Analytics aggregation

## Test Data and Factories

The tests use Laravel factories for consistent test data generation:

- `UserFactory` - Creates test users
- `UserProfileFactory` - Creates test profiles
- `UserSkillFactory` - Creates test skills
- `UserVolunteeringInterestFactory` - Creates test interests
- `UserVolunteeringHistoryFactory` - Creates test volunteering history
- `UserDocumentFactory` - Creates test documents
- `UserAlumniOrganizationFactory` - Creates test alumni data
- `UserRegistrationStepFactory` - Creates test registration steps
- `UserPlatformInterestFactory` - Creates test platform interests

## Test Environment Setup

### Prerequisites
- PHP 8.1+
- Laravel 10+
- MySQL/PostgreSQL database
- Redis (for caching tests)
- Chrome/Chromium (for browser tests)

### Configuration
1. Copy `.env.testing` configuration
2. Set up test database
3. Install Chrome driver for Dusk: `php artisan dusk:chrome-driver`
4. Run migrations: `php artisan migrate --env=testing`

### Continuous Integration
Tests are designed to run in CI environments with:
- Database seeding and migration
- File system mocking
- Cache and session mocking
- Email and notification mocking

## Test Maintenance

### Adding New Tests
1. Follow the existing directory structure
2. Use appropriate test base classes
3. Mock external dependencies
4. Include both positive and negative test cases
5. Add performance assertions where relevant
6. Include security test cases for new features

### Updating Tests
When modifying profile system features:
1. Update corresponding unit tests
2. Update integration tests if workflows change
3. Update browser tests if UI changes
4. Update performance benchmarks if needed
5. Review security implications

## Troubleshooting

### Common Issues
- **Database connection errors**: Check test database configuration
- **File permission errors**: Ensure storage directories are writable
- **Browser test failures**: Update Chrome driver version
- **Performance test failures**: Adjust thresholds based on environment

### Debug Mode
Run tests with verbose output:
```bash
php artisan test --verbose tests/Feature/Profile/
```

### Test Isolation
Each test runs in a transaction that's rolled back, ensuring test isolation.

## Metrics and Reporting

### Coverage Reports
Generate coverage reports:
```bash
php artisan test --coverage-html coverage-report/
```

### Performance Benchmarks
Performance tests include assertions for:
- Response times (< 500ms for complex operations)
- Query counts (< 20 queries for profile loading)
- Memory usage optimization
- Caching effectiveness

### Security Validation
Security tests validate:
- Authentication and authorization
- Input validation and sanitization
- File upload restrictions
- Rate limiting effectiveness
- Privacy setting enforcement