# .docker/data

Please map persistent volumes to this directory on the servers.

If a container needs to persist data between restarts you can map the relevant files in the container to ``docker/data/<container-name>`.
