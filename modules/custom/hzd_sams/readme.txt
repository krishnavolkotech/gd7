@beta4
Installationsanleitung

Die Funktionalität dieses Moduls wird über Routen gewährleistet.
Der Zugriff zu diesen Routen erfolgt über die Gruppe SAMS.

Folgende Module müssen ebenfalls installiert sein:
  - hzd_artifact_comments
  - hzd_notifications

1. Link zu Gruppe SAMS KONSENS in der Hauptnavigation unter "ZPS & ZOE"
2. Erstellen der folgenden Menüstruktur innerhalb der Gruppe "SAMS":
  Allgemeines
  Ansprechpartner
  Software Artefakte            [ sams-konsens/software-artefakte ] (Neue Seite)
    Bibliotheken                [ sams-konsens/software-artefakte/bibliotheken ]
    Entwicklungsversionen       [ sams-konsens/software-artefakte/entwicklungsversionen ]
    Mock-Objekte                [ sams-konsens/software-artefakte/mock-objekte ]
    Schema                      [ sams-konsens/software-artefakte/schema ]
    Norm                        [ sams-konsens/software-artefakte/norm ]
  { Alle Software Artefakte } [ sams-konsens/software-artefakte/performance-test ]
  FAQ
  Forum
  Gruppenmitglieder
  { Mailtrigger }             [ send-sams-mail ]
3. Neuer Inhaltstyp: Artefakt-Kommentar
    Beschriftung  | Systemname            | Feldtyp
    Artefakt      | field_artifact_name   | Klartext
    Body          | body                  | Text (formatiert, lang, mit Zusammenfassung)
    Kommentare    | comment_no_subject    | Kommentare
4. Inhaltstyp Artefakt-Kommentar muss als Plugin dem Gruppentyp "offen" hinzugefügt werden
    - Gruppeninhalt installieren
    - Haken bei "2-step-wizard" entfernen
5. update.php ausführen