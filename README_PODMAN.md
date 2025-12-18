# Anleitung zur Verwendung mit Podman

Dieses Projekt kann mit Podman und Podman Compose ausgeführt werden.

## Voraussetzungen

- [Podman](https://podman.io/)
- [Podman Compose](https://github.com/containers/podman-compose) (oder Docker Compose)

## Starten der Container

Um die Anwendung zu starten, führen Sie den folgenden Befehl im Hauptverzeichnis des Projekts aus:

```bash
podman-compose up --build
```

Oder, falls Sie `docker-compose` verwenden:

```bash
docker-compose up --build
```

Die Anwendung ist dann unter [http://localhost:8080](http://localhost:8080) erreichbar.

## Datenbank

Die Datenbank wird automatisch mit der Datei `router_game.sql` initialisiert.
Die Datenbankdaten werden in einem Volume `db_data` gespeichert.

## Stoppen der Container

Um die Container zu stoppen und zu entfernen:

```bash
podman-compose down
```
