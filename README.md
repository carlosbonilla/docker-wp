# docker-wp

A small WordPress development environment: the official WordPress Apache image,
MariaDB, and two persistent Docker volumes. No image build or GitHub credentials
are required to clone this public repository.

## Requirements

- Docker Engine and Docker Compose v2 or later.
- Git and Python 3 for the one-time configuration below.
- An unused TCP port, default 8080.

## Deploy from scratch

1. Clone the repository on the machine that will run WordPress:

   ```sh
   git clone https://github.com/carlosbonilla/docker-wp.git
   cd docker-wp
   ```

2. Generate private database credentials once. This refuses to overwrite an
   existing `.env`. Run it from the repository root:

   ```sh
   python3 - <<'PYTHON'
   import os
   from pathlib import Path
   import secrets

   content = Path('.env.example').read_text()
   for key in ('WORDPRESS_DB_PASSWORD', 'MARIADB_ROOT_PASSWORD'):
       content = content.replace(f'{key}=\n', f'{key}={secrets.token_hex(32)}\n')
   descriptor = os.open('.env', os.O_WRONLY | os.O_CREAT | os.O_EXCL, 0o600)
   with os.fdopen(descriptor, 'w') as config:
       config.write(content)
   print('Created private .env; existing credentials will be reused on later runs.')
   PYTHON
   ```

3. For access from a trusted LAN, edit `.env` and set
   `WORDPRESS_BIND_ADDRESS` to the machine's LAN IPv4 address or `0.0.0.0`.
   Allow TCP 8080 from that LAN in the VM firewall if one is enabled.
   Leave the database port unpublished. The default binds HTTP to loopback.

4. Validate and start:

   ```sh
   docker compose config --quiet
   docker compose up -d --wait --wait-timeout 180
   docker compose ps
   ```

5. Open `http://<machine-ip>:8080` (or `http://localhost:8080` for loopback).
   Complete the WordPress installer and choose a separate administrator account
   and password. Save that login in your password manager. Database credentials
   remain available to the deployment owner in the ignored `.env` (mode 0600).
   This development setup uses HTTP on a trusted network; public hosting needs
   a separately configured HTTPS deployment.

## Change and deploy code

`mu-plugins/` is mounted read-only into WordPress. PHP plugins placed directly in
this directory load automatically; edit them in Git rather than inside a
container. Keep the tracked `.gitkeep` in this directory so Git does not remove
and recreate the directory while it is bind-mounted. WordPress core, uploads,
themes, and database content persist in the
named volumes. The bind mount requires a local checkout on the Docker host.

After committing and pushing a change, run on the deployment machine:

```sh
git pull --ff-only origin main
docker compose config --quiet
docker compose up -d --wait --wait-timeout 180
```

The included deployment-check plugin displays "Deployed from GitHub with
docker-wp." at the bottom of the public site. This provides a visible check
that a pushed and pulled code change reached WordPress.

Refresh the page after a few seconds: PHP's image defaults may briefly cache
changed code. If a checkout operation has replaced the mounted directory,
recreate only the application container to refresh its bind mount:

```sh
docker compose up -d --force-recreate --wait wordpress
```

An unchanged ordinary `up` reuses the containers, volumes, and credentials.
The public clone needs no GitHub login; pushing from your computer requires your
normal GitHub authentication. Agent account authentication is independent.

Image references, bind address, and port are configurable through `.env`.
Keep the database passwords unchanged after initialization: changing `.env`
does not rotate passwords in an existing MariaDB volume. The WordPress volume
also retains its installed core; changing an image tag alone does not guarantee
an existing site has upgraded. Back up the site before planning upgrades.

## Stop, resume, and verify persistence

Create a draft or published post in the browser, then run:

```sh
docker compose down
docker compose up -d --wait --wait-timeout 180
```

Sign in again and confirm the post remains. `down` removes this project's
containers and network while preserving its volumes. Never add `--volumes` when
you need to keep the site. No volume deletion is part of normal deployment.

For an application rollback, check out a previously validated commit on a clean
working tree and run `docker compose up -d --wait` again. Database and WordPress
core upgrades require a separate backup and recovery plan. To withdraw LAN
access, stop this Compose project and remove only its HTTP allow rule from the
VM firewall.

## References

- [Official WordPress image](https://hub.docker.com/_/wordpress)
- [Official MariaDB image](https://hub.docker.com/_/mariadb)
- [Compose startup readiness](https://docs.docker.com/compose/how-tos/startup-order/)
