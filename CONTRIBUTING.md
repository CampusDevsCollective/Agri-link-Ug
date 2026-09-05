# Contributing to AgriLink

## Branches

- main - always working, used for demos/submission
- dev - integration branch, merge finished features here first
- feature/<short-name> - one branch per feature, off dev
- fix/<short-name> - bug fixes

## Workflow

1. Branch off dev: git checkout -b feature/matching-engine dev
2. Commit small, working changes
3. Push and open a Pull Request into dev
4. At least one teammate reviews before merging
5. dev merges into main only when stable

## Commit messages

feat(backend): add produce_listings model
fix(frontend): correct buyer registration validation
docs: add ERD and workflow diagrams

## Issues

One issue per feature/bug. Label with backend, frontend, docs, or bug. Assign an owner.
