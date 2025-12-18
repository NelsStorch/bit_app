# Anleitung zur Verwendung mit Docker

Dieses Projekt kann mit Docker und Docker Compose ausgeführt werden.

## Voraussetzungen

- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)

## Starten der Container

Um die Anwendung zu starten, führen Sie den folgenden Befehl im Hauptverzeichnis des Projekts aus:

```bash
docker compose up --build
```

Die Anwendung ist dann unter [http://localhost:8080](http://localhost:8080) erreichbar.

## Datenbank

Die Datenbank wird automatisch mit der Datei `database/router_game.sql` initialisiert.
Die Datenbankdaten werden in einem Volume `db_data` gespeichert.

## Stoppen der Container

Um die Container zu stoppen und zu entfernen:

```bash
docker compose down
```
