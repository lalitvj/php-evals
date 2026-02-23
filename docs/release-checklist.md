# Release Checklist

1. Verify branch is synced with `master`.
2. Run `make qa` and confirm all checks pass.
3. Verify docs are updated:
   - installation
   - quickstarts
   - assertions
   - function-calling
   - CI
   - extending
4. Confirm changelog entries are added.
5. Validate CLI behavior:
   - `--suite`
   - `--format=table|json`
   - `--stop-on-failure`
   - exit codes `0/1/2`
6. Validate Laravel bridge command: `php artisan ai:eval`.
7. Ensure CI workflow is green on supported PHP versions.
8. Tag release and publish notes.
