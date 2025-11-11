## Description
<!-- Provide a brief description of the changes in this PR -->

## Type of Change
<!-- Mark the relevant option with an "x" -->
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Refactoring (no functional changes, code quality improvements)
- [ ] Documentation update
- [ ] Performance improvement
- [ ] Security fix
- [ ] Tests

## Related Issues
<!-- Link related issues here. Use "Fixes #123" or "Closes #123" to auto-close issues when merged -->
Fixes #

## Changes Made
<!-- Describe the changes in detail -->
- 
- 
- 

## Testing
<!-- Describe the tests you ran to verify your changes -->

### Test Configuration
- **PHP Version**: 
- **Database**: 
- **Environment**: [dev/test/staging]

### Test Cases
- [ ] Unit tests pass (`composer test`)
- [ ] Integration tests pass
- [ ] Manual testing performed
- [ ] API endpoints tested with Postman/curl
- [ ] Multi-tenancy isolation verified (if applicable)

### Test Results
```
Paste relevant test output here
```

## Code Quality
<!-- All checkboxes must be checked before merging -->
- [ ] Code follows PSR-12 standards (`composer cs-check`)
- [ ] PHPStan analysis passes (`composer phpstan`)
- [ ] All tests pass (`composer test`)
- [ ] Code is properly documented (PHPDoc comments)
- [ ] No security vulnerabilities introduced
- [ ] Backward compatibility maintained (or breaking changes documented)

## Database Changes
<!-- If this PR includes database changes, describe them -->
- [ ] No database changes
- [ ] Migration files added
- [ ] Migration tested locally
- [ ] Rollback tested

### Migration Details
<!-- If applicable, describe the database changes -->
```sql
-- Paste relevant SQL here
```

## API Changes
<!-- If this PR changes the API, document the changes -->
- [ ] No API changes
- [ ] New endpoints added
- [ ] Existing endpoints modified
- [ ] API documentation updated

### Endpoint Changes
<!-- Document any new or modified endpoints -->
```
POST /api/new-endpoint - Description
PATCH /api/existing-endpoint - What changed
```

## Security Considerations
<!-- Describe any security implications or considerations -->
- [ ] No security implications
- [ ] Security review required
- [ ] Authentication/Authorization changes
- [ ] Input validation added/updated
- [ ] Sensitive data handling reviewed

## Performance Impact
<!-- Describe any performance implications -->
- [ ] No performance impact
- [ ] Performance improved
- [ ] Performance impact assessed and acceptable
- [ ] Database indexes added/updated (if applicable)

## Documentation
<!-- Update relevant documentation -->
- [ ] README.md updated (if needed)
- [ ] API documentation updated (if needed)
- [ ] Code comments added/updated
- [ ] Migration guide provided (for breaking changes)

## Screenshots/Videos
<!-- If applicable, add screenshots or videos demonstrating the changes -->

## Deployment Notes
<!-- Any special instructions for deployment -->
- [ ] No special deployment steps required
- [ ] Environment variables need to be updated
- [ ] Migrations need to be run
- [ ] Cache needs to be cleared
- [ ] Other (specify below):

### Deployment Steps
<!-- List any special deployment steps -->
1. 
2. 

## Checklist
<!-- All items must be checked before requesting review -->
- [ ] My code follows the project's coding standards
- [ ] I have performed a self-review of my code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes
- [ ] Any dependent changes have been merged and published

## Reviewer Notes
<!-- Any special instructions or context for reviewers -->

## Post-Merge Tasks
<!-- List any tasks that need to be done after merging -->
- [ ] Deploy to staging
- [ ] Update related documentation
- [ ] Notify team of changes
- [ ] Other:
