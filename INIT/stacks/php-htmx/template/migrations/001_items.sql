-- Schéma de départ. Une migration = un fichier numéroté, jamais modifié une fois appliqué : on en ajoute un nouveau.
CREATE TABLE items (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    label      TEXT    NOT NULL CHECK (length(label) BETWEEN 1 AND 200),
    created_at TEXT    NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%SZ', 'now'))
);
