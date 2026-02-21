.PHONY: help install test phpstan cs-fix cs-check infection deptrac quality security-check lint ci install-hooks

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Install dependencies
	composer install

test: ## Run PHPUnit tests
	vendor/bin/phpunit

phpstan: ## Static analysis (level 9)
	vendor/bin/phpstan analyse --memory-limit=512M

cs-fix: ## Fix code style
	vendor/bin/php-cs-fixer fix

cs-check: ## Check code style (no fix)
	vendor/bin/php-cs-fixer fix --dry-run --diff

infection: ## Mutation testing
	vendor/bin/infection --only-covered --show-mutations --threads=max --min-msi=70

deptrac: ## Architecture enforcement
	vendor/bin/deptrac analyse

security-check: ## Dependency security audit
	composer audit

lint: ## Lint translation files
	@test -d translations && find translations -name '*.xlf' -exec xmllint --noout {} + 2>/dev/null || true

quality: cs-check phpstan deptrac lint test infection ## Full quality pipeline

ci: security-check quality ## Full CI pipeline (security + quality)

install-hooks: ## Install git hooks (strips Co-authored-by from commits)
	cp scripts/git-hooks/commit-msg .git/hooks/commit-msg
	chmod +x .git/hooks/commit-msg
	@echo "Git hooks installed."
