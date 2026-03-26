# claude --resume 1b667a73-02e0-4454-be22-a28282363e70
claude: _claude_volume_create ## claude
	- docker build \
		--build-arg UID=$(USER) \
		--build-arg WORKDIR=/app \
		--target cli \
		-t app_claude .docker/claude
	- docker run --init --rm -it \
		-u $(USER) \
		-v "migration_claude:/home/developer" \
		-v "$$(pwd)/src:/app/src" \
		-v "$$(pwd)/tests:/app/tests" \
		-v "$$(pwd)/example:/app/example" \
		-v "$$(pwd)/vendor:/app/vendor" \
		-v "$$(pwd)/composer.json:/app/composer.json" \
		-v "$$(pwd)/infection.json5.dist:/app/infection.json5.dist" \
		-v "$$(pwd)/Makefile:/app/Makefile" \
		-v "$$(pwd)/phpcs.xml.dist:/app/phpcs.xml.dist" \
		-v "$$(pwd)/phpstan.neon:/app/phpstan.neon" \
		-v "$$(pwd)/phpunit.xml.dist:/app/phpunit.xml.dist" \
		-v "$$(pwd)/psalm.xml:/app/psalm.xml" \
		-v "$$(pwd)/README.md:/app/README.md" \
		-v "$$(pwd)/rector.php:/app/rector.php" \
		-v "$$(pwd)/.claude/CLAUDE.md:/app/CLAUDE.md" \
		-w /app \
		app_claude bash -l
	- docker image rm -f app_claude

claude_backup: _store-create _store-backup _store-remove

claude_restore: _store-create _store-restore _store-remove

_claude_volume_create:
	docker volume create migration_claude

_claude_volume_remove:
	docker volume rm -f migration_claude

_store-create:
	docker run -u $(USER) -v migration_claude:/data --name claude_store alpine sh

_store-remove:
	docker rm -f claude_store

_store-backup:
	docker run --rm --volumes-from claude_store \
		-u $(USER) \
		-v "$$(pwd)/.claude/backup:/backup" \
		alpine tar czvf /backup/claude.tar.gz data/

_store-restore:
	docker run --rm --volumes-from claude_store \
		-u $(USER) \
		-v "$$(pwd)/.claude/backup:/backup" \
		alpine bash -c "cd /data && rm -rf /data && tar xzvf /backup/claude.tar.gz --strip-components 1"
