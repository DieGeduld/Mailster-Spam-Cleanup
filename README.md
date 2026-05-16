# Mailster Spam Cleanup

Ein leichtgewichtiges WordPress-Plugin, um unerwünschte Abonnenten (Spam) in [Mailster](https://mailster.co/) massenhaft zu deaktivieren oder endgültig zu löschen.

## Funktionen

* **Massenverarbeitung:** Verarbeite Hunderte oder Tausende von E-Mail-Adressen in einem Rutsch.
* **Zwei Eingabemethoden:**
  * **Manuelle Eingabe:** Einfach eine Liste von E-Mail-Adressen (eine pro Zeile) in ein Textfeld kopieren.
  * **CSV-Upload:** Lade eine CSV-Datei hoch und wähle die Spalte aus, die die E-Mail-Adressen enthält.
* **Zwei Aktionen:**
  * **Deaktivieren (Unsubscribe):** Setzt den Status der Abonnenten auf "Abgemeldet". Sie bleiben im System (z.B. für Historie oder Blacklisting), erhalten aber keine Newsletter mehr.
  * **Löschen (Delete):** Entfernt die Abonnenten dauerhaft und unwiderruflich aus der Mailster-Datenbank.
* **Vorschau-Modus:** Bevor Änderungen vorgenommen werden, zeigt das Plugin an, welche E-Mail-Adressen in Mailster gefunden wurden und welche nicht.
* **Nahtlose Integration:** Das Plugin-Menü fügt sich direkt in das "Newsletter" (Mailster) Hauptmenü ein.

## Installation

1. Lade den Ordner `mailster-spam-cleanup` in das Verzeichnis `/wp-content/plugins/` deiner WordPress-Installation hoch (oder klone das Repository direkt dorthin).
2. Aktiviere das Plugin "Mailster Spam Cleanup" im WordPress-Backend unter **Plugins**.
3. Navigiere im Backend im **Newsletter**-Menü zu **Spam Cleanup**.

## Verwendung

1. Gehe zu **Newsletter → Spam Cleanup**.
2. **E-Mail-Adressen angeben:**
   * Wähle den Tab **Manuell eingeben**, um Adressen direkt in das Textfeld einzufügen.
   * Oder wähle **Eigene CSV hochladen**, um eine Datei hochzuladen. Nach Auswahl der Datei kannst du die Spalte angeben, in der sich die E-Mail-Adressen befinden.
3. **Aktion wählen:** Entscheide, ob die gefundenen Adressen nur deaktiviert oder komplett gelöscht werden sollen.
4. Klicke auf **E-Mails prüfen (Vorschau)**.
5. In der Vorschau siehst du, wie viele der angegebenen E-Mail-Adressen in der Datenbank gefunden wurden.
6. Bestätige die Ausführung mit einem Klick auf **Jetzt endgültig ausführen**. Eine Tabelle zeigt dir danach den Erfolgsstatus für jeden Abonnenten an.

## Systemanforderungen

* WordPress 5.0 oder höher
* Mailster Newsletter-Plugin (aktiviert)
* PHP 7.4 oder höher

## Autor

**Unkonventionell**
