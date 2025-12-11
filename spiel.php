<?php
/**
 * Datei: spiel.php
 * Beschreibung: Hauptlogik für das Spiel "Geheime Rolle Xtreme".
 * Dieses Skript handhabt die gesamte Spiellogik, einschliesslich Zustandsverwaltung (State Management) via Session,
 * Rollenverteilung, Spielphasen und Benutzeroberfläche.
 *
 * Das Spiel ist ein Party-Spiel ähnlich wie "Spyfall" oder "Werwolf",
 * bei dem Spieler geheime Rollen und Wörter erhalten.
 */

session_start();

// --- Globale Konfiguration & Wortliste ---
$spielName = "Geheime Rolle Xtreme (Zufallsstart & Coole Wörter)";
$spielerAnzahlOptionen = [3, 4, 5, 6, 7, 8];

/**
 * Liste von Wortpaaren für das Spiel.
 * Struktur: [Hauptwort, Ähnliches Wort]
 * Das "Hauptwort" bekommen die "Normalos", das "Ähnliche Wort" bekommen die "Undercover"-Spieler.
 * "Mr. White" bekommt gar kein Wort.
 */
$wortPaare = [
    ["Meme", "GIF"],
    ["Influencer", "YouTuber"],
    ["Podcast", "Radiosendung"],
    ["Superheld", "Actionfigur"],
    ["Festival", "WG-Party"],
    ["Escape Room", "Schnitzeljagd"],
    ["Streamingdienst", "Videothek"],
    ["Kryptowährung", "Aktie"],
    ["Avatar", "Profilbild"],
    ["Challenge", "Mutprobe"],
    ["Foodtruck", "Imbissbude"],
    ["Sneaker", "Turnschuh"],
    ["Playlist", "Mixtape"],
    ["Smartwatch", "Armbanduhr"],
    ["Start-up", "Projekt"],
    ["Homeoffice", "Büro"],
    ["Roadtrip", "Ausflug"],
    ["Gaming-PC", "Konsole"],
    ["DIY-Projekt", "Handwerksstück"],
    ["Nachhaltigkeit", "Recycling"],
    ["Virtual Reality", "Augmented Reality"],
    ["Cosplay", "Kostümparty"],
    ["Street Art", "Graffiti"],
    ["Blog", "Online-Tagebuch"],
    ["Drohne", "Modellflugzeug"]
];


// --- Phasen des Spiels (Dokumentation) ---
// Das Spiel durchläuft folgende Phasen (State Machine in $_SESSION['game_phase']):
// 1. 'setup_spieleranzahl': Auswahl der Spieleranzahl.
// 2. 'setup_namen': Eingabe der Spielernamen.
// 3. 'rollen_ansehen_zwischenschritt': "Pass the Device" - Aufforderung, das Gerät weiterzugeben.
// 4. 'rollen_anzeigen': Anzeige der geheimen Rolle für den aktuellen Spieler.
// 5. 'hinweisrunde_info': Informationsbildschirm für die Diskussionsrunde.
// 6. 'abstimmung_verbal': Abstimmung, wer eliminiert werden soll.
// 7. 'mr_white_guesst': Spezialphase, falls Mr. White eliminiert wird (er darf raten).
// 8. 'zwischen_aufloesung': Anzeige des Ergebnisses einer Rauswahl.
// 9. 'endgueltige_aufloesung': Spielende und Siegerehrung.

// Aktuelle Aktion abrufen (GET oder POST)
$action = $_POST['action'] ?? ($_GET['action'] ?? null);

/**
 * Initialisiert ein neues Spiel oder eine neue Runde.
 *
 * Diese Funktion setzt die Session-Variablen zurück, wählt ein zufälliges Wortpaar,
 * verteilt die Rollen (Mr. White, Undercover, Normalo, Mister Meme) zufällig an die Spieler
 * und setzt den Spielstatus auf den Anfang.
 *
 * @param bool $is_new_full_game Wenn true, werden alle Spielerdaten gelöscht (komplett neues Spiel).
 * @param bool $keep_players Wenn true, werden die Spielernamen behalten (neue Runde mit gleichen Spielern).
 * @return bool Gibt true zurück, wenn die Initialisierung erfolgreich war, sonst false.
 */
function init_new_game_or_round($is_new_full_game = true, $keep_players = false) {
    global $wortPaare, $spielerAnzahlOptionen; // Zugriff auf globale Variablen

    // Fall: Komplett neues Spiel (Spieler müssen neu eingegeben werden)
    if ($is_new_full_game && !$keep_players) {
        unset($_SESSION['spieler_daten'], $_SESSION['original_namen'], $_SESSION['anzahl_spieler']);
        $_SESSION['game_phase'] = 'setup_spieleranzahl';
        return true;
    }

    // Fall: Spielerdaten verarbeiten oder prüfen
    if (!$keep_players || !isset($_SESSION['original_namen'])) {
        if (isset($_POST['spieler_name']) && isset($_SESSION['anzahl_spieler'])) {
            // Namen aus Formular übernehmen
            $namen = $_POST['spieler_name'];
            if (count($namen) != $_SESSION['anzahl_spieler']) { $_SESSION['error_message'] = "Anzahl Namen passt nicht."; return false; }
            foreach ($namen as $name) { if (empty(trim($name))) { $_SESSION['error_message'] = "Namen dürfen nicht leer sein."; return false; }}
            $_SESSION['original_namen'] = array_map('trim', $namen);
        } else if (!$is_new_full_game && isset($_SESSION['original_namen'])) {
            // Namen sind für Folgerunde schon da
        } else {
            // Keine Namen vorhanden -> zurück zum Setup
            $_SESSION['game_phase'] = 'setup_spieleranzahl'; return false;
        }
    }
    
    $namen_fuer_runde = $_SESSION['original_namen'];
    $anzahl_spieler_fuer_runde = count($namen_fuer_runde);
    $_SESSION['anzahl_spieler'] = $anzahl_spieler_fuer_runde;

    // Wortpaar auswählen
    $aktuelles_wortpaar = $wortPaare[array_rand($wortPaare)];
    $_SESSION['haupt_wort'] = $aktuelles_wortpaar[0];
    $_SESSION['aehnliches_wort'] = $aktuelles_wortpaar[1];

    // Indizes mischen für zufällige Rollenverteilung
    $spielerIndizes = range(0, $anzahl_spieler_fuer_runde - 1);
    shuffle($spielerIndizes);

    $tempSpielerDaten = [];

    // Bestimmen, wie viele Undercover-Spieler dabei sind
    $num_undercover = 0;
    if ($anzahl_spieler_fuer_runde >= 6) $num_undercover = 2;
    elseif ($anzahl_spieler_fuer_runde >= 3) $num_undercover = 1;

    // Rollen zuweisen
    if ($anzahl_spieler_fuer_runde >= 1) { // Mr. White zuweisen
        $mrWhiteOriginalIndex = array_pop($spielerIndizes);
        $tempSpielerDaten[$mrWhiteOriginalIndex] = [
            'name' => $namen_fuer_runde[$mrWhiteOriginalIndex], 'angezeigte_rolle' => 'Mr. White',
            'tatsaechliche_rolle' => 'Mr. White', 'wort' => '--- (Du bist Mr. White!) ---',
            'id' => 'spieler_' . $mrWhiteOriginalIndex, 'nebenrolle' => null, 'aktiv' => true
        ];
    }
    for ($i = 0; $i < $num_undercover; $i++) { // Undercover(s) zuweisen
        if (!empty($spielerIndizes)) {
            $undercoverOriginalIndex = array_pop($spielerIndizes);
            $tempSpielerDaten[$undercoverOriginalIndex] = [
                'name' => $namen_fuer_runde[$undercoverOriginalIndex], 'angezeigte_rolle' => 'Normalo',
                'tatsaechliche_rolle' => 'Undercover', 'wort' => $_SESSION['aehnliches_wort'],
                'id' => 'spieler_' . $undercoverOriginalIndex, 'nebenrolle' => null, 'aktiv' => true
            ];
        }
    }
    foreach ($spielerIndizes as $normaloOriginalIndex) { // Normalos zuweisen
        $tempSpielerDaten[$normaloOriginalIndex] = [
            'name' => $namen_fuer_runde[$normaloOriginalIndex], 'angezeigte_rolle' => 'Normalo',
            'tatsaechliche_rolle' => 'Normalo', 'wort' => $_SESSION['haupt_wort'],
            'id' => 'spieler_' . $normaloOriginalIndex, 'nebenrolle' => null, 'aktiv' => true
        ];
    }
    
    // Nach Index sortieren, damit die Reihenfolge der Eingabe entspricht
    ksort($tempSpielerDaten);
    $_SESSION['spieler_daten'] = array_values($tempSpielerDaten);
    
    // Zusatzrolle "Mister Meme" (optionaler Spaßfaktor)
    if ($anzahl_spieler_fuer_runde >= 2) {
        $potentialMemePlayersIndices = [];
        foreach($_SESSION['spieler_daten'] as $idx => $spieler) {
            if ($spieler['tatsaechliche_rolle'] != 'Mr. White') { $potentialMemePlayersIndices[] = $idx; }
        }
        if (!empty($potentialMemePlayersIndices)) {
            $memePlayerIndex = $potentialMemePlayersIndices[array_rand($potentialMemePlayersIndices)];
            $_SESSION['spieler_daten'][$memePlayerIndex]['nebenrolle'] = 'Mister Meme';
        }
    }

    // Status-Flags zurücksetzen
    foreach($_SESSION['spieler_daten'] as $key => $spieler) { $_SESSION['spieler_daten'][$key]['hat_gesehen'] = false; }

    $_SESSION['aktueller_spieler_index_ansicht'] = 0;
    $_SESSION['game_phase'] = 'rollen_ansehen_zwischenschritt';
    // Aufräumen von alten Session-Daten
    unset($_SESSION['letzter_rausgewaehlter_spieler_id'], $_SESSION['mr_white_guess_word'], $_SESSION['mr_white_guessed_correctly'], $_SESSION['error_message'], $_SESSION['spiel_gewinner_nachricht'], $_SESSION['aktueller_runden_start_spieler_name']);
    return true;
}

/**
 * Wählt zufällig einen Startspieler für die Hinweisrunde aus den noch aktiven Spielern.
 */
function set_random_hinweis_start_spieler() {
    $aktiveSpielerNamen = [];
    if (isset($_SESSION['spieler_daten'])) {
        foreach($_SESSION['spieler_daten'] as $spieler) {
            if ($spieler['aktiv']) {
                $aktiveSpielerNamen[] = $spieler['name'];
            }
        }
    }
    if (!empty($aktiveSpielerNamen)) {
        $_SESSION['aktueller_runden_start_spieler_name'] = $aktiveSpielerNamen[array_rand($aktiveSpielerNamen)];
    } else {
        $_SESSION['aktueller_runden_start_spieler_name'] = "Niemandem (Fehler)"; // Fallback
    }
}

// --- Aktionen verarbeiten (Controller-Logik) ---

// 1. Reset Full Game
if ($action == 'reset_full_game') {
    session_destroy(); session_start(); init_new_game_or_round(true, false);
    header("Location: spiel.php"); exit;
}

// 2. Nächste Runde mit gleichen Spielern
if ($action == 'next_full_game_same_players') {
    if (isset($_SESSION['original_namen'])) { init_new_game_or_round(false, true); }
    else { $_SESSION['game_phase'] = 'setup_spieleranzahl'; }
    header("Location: spiel.php"); exit;
}

// 3. Spieleranzahl setzen
if ($action == 'set_spieleranzahl' && isset($_POST['anzahl_spieler'])) {
    $anzahl = (int)$_POST['anzahl_spieler'];
    if (in_array($anzahl, $spielerAnzahlOptionen)) {
        $_SESSION['anzahl_spieler'] = $anzahl; $_SESSION['game_phase'] = 'setup_namen'; unset($_SESSION['original_namen']);
    } else { $_SESSION['error_message'] = "Ungültige Spieleranzahl."; }
    header("Location: spiel.php"); exit;
}

// 4. Initiales Spiel mit Namen starten
if ($action == 'start_initial_game_with_names') {
    if(!init_new_game_or_round(false, false)) { $_SESSION['game_phase'] = 'setup_namen'; }
    header("Location: spiel.php"); exit;
}

// 5. Rolle gesehen bestätigen
if ($action == 'rolle_gesehen' && isset($_POST['spieler_index_gesehen'])) {
    $spielerIndex = (int)$_POST['spieler_index_gesehen'];
    if (isset($_SESSION['spieler_daten'][$spielerIndex])) { $_SESSION['spieler_daten'][$spielerIndex]['hat_gesehen'] = true; }

    // Prüfen, ob alle ihre Rolle gesehen haben
    $alleGesehen = true; $naechsterIndex = -1;
    for ($i = 0; $i < count($_SESSION['spieler_daten']); $i++) {
        if ($_SESSION['spieler_daten'][$i]['aktiv'] && !$_SESSION['spieler_daten'][$i]['hat_gesehen']) {
            $alleGesehen = false; $naechsterIndex = $i; break;
        }
    }
    
    if ($alleGesehen) {
        $_SESSION['game_phase'] = 'hinweisrunde_info';
        set_random_hinweis_start_spieler(); // Zufälligen Startspieler für die ERSTE Hinweisrunde setzen
    } else {
        $_SESSION['aktueller_spieler_index_ansicht'] = $naechsterIndex;
        $_SESSION['game_phase'] = 'rollen_ansehen_zwischenschritt';
    }
    header("Location: spiel.php"); exit;
}

// 6. Zur verbalen Abstimmung gehen
if ($action == 'start_abstimmung_verbal') {
    $_SESSION['game_phase'] = 'abstimmung_verbal';
    header("Location: spiel.php"); exit;
}

// 7. Ergebnis der verbalen Abstimmung verarbeiten (Spieler rauswählen)
if ($action == 'submit_verbal_vote' && isset($_POST['voted_player_id'])) {
    $votedPlayerId = $_POST['voted_player_id'];
    $_SESSION['letzter_rausgewaehlter_spieler_id'] = $votedPlayerId;
    $votedPlayerTatsaechlicheRolle = null; $votedPlayerIndex = -1;

    foreach($_SESSION['spieler_daten'] as $idx => $spieler) {
        if ($spieler['id'] == $votedPlayerId) {
            $votedPlayerTatsaechlicheRolle = $spieler['tatsaechliche_rolle'];
            $votedPlayerIndex = $idx; break;
        }
    }

    if ($votedPlayerIndex !== -1) {
        $_SESSION['spieler_daten'][$votedPlayerIndex]['aktiv'] = false; // Spieler deaktivieren
        if ($votedPlayerTatsaechlicheRolle == 'Mr. White') {
            $_SESSION['game_phase'] = 'mr_white_guesst'; // Spezialphase für Mr. White
        } else {
            $_SESSION['game_phase'] = 'zwischen_aufloesung'; // Normale Zwischenauflösung
        }
    } else {
        $_SESSION['game_phase'] = 'abstimmung_verbal';
    }
    header("Location: spiel.php"); exit;
}

// 8. Mr. White Rateversuch verarbeiten
if ($action == 'submit_mr_white_guess' && isset($_POST['word_guess'])) {
    $guess = trim($_POST['word_guess']);
    $_SESSION['mr_white_guess_word'] = $guess;
    // Prüfen ob das Wort korrekt ist (case-insensitive)
    $_SESSION['mr_white_guessed_correctly'] = (isset($_SESSION['haupt_wort']) && strtolower($guess) == strtolower(trim($_SESSION['haupt_wort'])));
    $_SESSION['game_phase'] = 'zwischen_aufloesung';
    header("Location: spiel.php"); exit;
}

// 9. Weiter nach Zwischenauflösung (Prüfung auf Spielende)
if ($action == 'continue_after_partial_reveal') {
    $aktiveSpieler = array_filter($_SESSION['spieler_daten'], function($p){ return $p['aktiv']; });
    $aktiveSpielerAnzahl = count($aktiveSpieler);
    $mrWhiteAktiv = false; $mrWhiteSpieler = null;
    foreach ($aktiveSpieler as $spieler) {
        if ($spieler['tatsaechliche_rolle'] == 'Mr. White') { $mrWhiteAktiv = true; $mrWhiteSpieler = $spieler; break; }
    }
    
    $letzterRausgewaehlter = null; $letzterRausgewaehlterRolle = null;
    if (isset($_SESSION['letzter_rausgewaehlter_spieler_id'])) {
        foreach($_SESSION['spieler_daten'] as $p) {
            if ($p['id'] == $_SESSION['letzter_rausgewaehlter_spieler_id']) {
                $letzterRausgewaehlter = $p; $letzterRausgewaehlterRolle = $p['tatsaechliche_rolle']; break;
            }
        }
    }

    $spielZuEnde = false;
    // Gewinnbedingungen prüfen
    if ($letzterRausgewaehlterRolle == 'Mr. White') { // Mr. White wurde in dieser Runde rausgewählt
        if (isset($_SESSION['mr_white_guessed_correctly']) && $_SESSION['mr_white_guessed_correctly']) {
            $_SESSION['spiel_gewinner_nachricht'] = "Mr. White (".htmlspecialchars($letzterRausgewaehlter['name']).") wurde erwischt, ABER hat das Hauptwort korrekt erraten! <strong>Mr. White gewinnt!</strong>";
            $spielZuEnde = true;
        } elseif (isset($_SESSION['mr_white_guessed_correctly']) && !$_SESSION['mr_white_guessed_correctly']) {
            $_SESSION['spiel_gewinner_nachricht'] = "Mr. White (".htmlspecialchars($letzterRausgewaehlter['name']).") wurde erwischt und hat FALSCH geraten! <strong>Die Normalos & Undercover gewinnen!</strong>";
            $spielZuEnde = true;
        }
    } else { // Ein Unschuldiger wurde rausgewählt
        if (!$mrWhiteAktiv) { // Mr. White war schon vorher raus -> Normalos gewinnen (sollte durch Logik oben abgefangen sein, aber zur Sicherheit)
            $_SESSION['spiel_gewinner_nachricht'] = "Mr. White wurde bereits eliminiert! <strong>Die Normalos & Undercover gewinnen!</strong>";
            $spielZuEnde = true;
        } elseif ($aktiveSpielerAnzahl <= 2 && $mrWhiteAktiv) { // Nur noch Mr. White und 1 anderer
            $_SESSION['spiel_gewinner_nachricht'] = "Nur noch ".htmlspecialchars($mrWhiteSpieler['name'] ?? 'Mr. White')." und ein weiterer Spieler sind übrig! <strong>Mr. White gewinnt durch Überleben!</strong>";
            $spielZuEnde = true;
        } elseif ($aktiveSpielerAnzahl < 2 && !$mrWhiteAktiv){
             $_SESSION['spiel_gewinner_nachricht'] = "Alle Spieler wurden eliminiert! Das ist...seltsam. Unentschieden?";
             $spielZuEnde = true;
        }
    }

    if ($spielZuEnde) {
        $_SESSION['game_phase'] = 'endgueltige_aufloesung';
    } else {
        set_random_hinweis_start_spieler(); // Zufälligen Startspieler für die NÄCHSTE Hinweisrunde setzen
        $_SESSION['game_phase'] = 'hinweisrunde_info';
    }
    unset($_SESSION['letzter_rausgewaehlter_spieler_id']);
    header("Location: spiel.php"); exit;
}


// --- Initialisierung & Phasensteuerung für Anzeige ---
if (!isset($_SESSION['game_phase'])) { $_SESSION['game_phase'] = 'setup_spieleranzahl'; }
$current_phase = $_SESSION['game_phase'];
$aktiverSpielerIndex = $_SESSION['aktueller_spieler_index_ansicht'] ?? 0;
$aktiveSpielerFuerAnzeige = isset($_SESSION['spieler_daten']) ? array_filter($_SESSION['spieler_daten'], function($p){ return $p['aktiv']; }) : [];

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($spielName); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS-Variablen für konsistentes Styling */
        :root {
            --primary-color: #0d6efd; --secondary-color: #6c757d; --success-color: #198754;
            --danger-color: #dc3545; --warning-color: #ffc107; --info-color: #0dcaf0;
            --purple-color: #6f42c1; --light-bg: #f8f9fa; --dark-text: #212529;
            --medium-text: #495057; --border-color: #dee2e6; --card-shadow: 0 4px 8px rgba(0,0,0,0.05);
            --border-radius: 0.375rem;
        }
        body { font-family: 'Nunito', sans-serif; margin: 0; padding: 20px; background-color: #e9ecef; color: var(--dark-text); display: flex; justify-content: center; align-items: flex-start; min-height: 100vh; }
        .container { background-color: #ffffff; padding: 30px; border-radius: var(--border-radius); box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15); width: 100%; max-width: 600px; }
        h1, h2, h3 { color: var(--dark-text); text-align: center; margin-top: 0; }
        h1 { margin-bottom: 30px; font-size: 2em; font-weight: 700; }
        h2 { margin-bottom: 25px; font-size: 1.6em; font-weight: 600;}
        h3 { margin-bottom: 15px; font-size: 1.3em; font-weight: 600;}
        .button, a.button-link {
            background-color: var(--primary-color); color: white !important; padding: 0.75rem 1.25rem;
            border: none; font-size: 1rem; font-weight: 600; border-radius: var(--border-radius);
            cursor: pointer; text-decoration: none; display: block; width: 100%; margin-top: 20px;
            box-sizing: border-box; text-align: center; transition: background-color 0.15s ease-in-out, transform 0.1s ease;
        }
        .button:hover, a.button-link:hover { filter: brightness(90%); transform: translateY(-1px); }
        .button:active, a.button-link:active { transform: translateY(0px); filter: brightness(80%);}
        .button-secondary { background-color: var(--secondary-color); }
        .button-green { background-color: var(--success-color); }
        .button-red { background-color: var(--danger-color); }
        .button-orange { background-color: #fd7e14; }
        .button-purple { background-color: var(--purple-color); }


        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--medium-text); }
        .form-group input[type="text"], .form-group input[type="number"], .form-group select {
            width: 100%; padding: 0.75rem; border: 1px solid var(--border-color);
            border-radius: var(--border-radius); box-sizing: border-box; font-size: 1rem;
        }
        .form-group input[type="text"]:focus, .form-group select:focus {
            outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
        }
        .error { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; padding: 1rem; border-radius: var(--border-radius); margin-bottom:1.25rem; text-align:center;}
        .info-box, .pass-device-screen, .verbal-vote-box, .mr-white-guess-box, .partial-reveal-box {
            background-color: var(--light-bg); border: 1px solid var(--border-color);
            padding: 1.25rem; margin-bottom: 1.5rem; border-radius: var(--border-radius); text-align: center;
        }
        .pass-device-screen { background-color: #fff3cd; border-color: #ffecb5; color: #664d03; }
        .pass-device-screen h2, .verbal-vote-box h2, .mr-white-guess-box h2, .partial-reveal-box h2 { color: inherit; }
        .pass-device-screen strong { font-weight: 700; }

        .role-display { background-color: #cff4fc; border:1px solid #9eeaf9; color:#055160; padding: 1.5rem; margin: 1.5rem 0; border-radius: var(--border-radius); text-align: center; }
        .role-display p { margin: 0.5rem 0; font-size: 1.15em; } .role-display strong { font-weight: 700; }
        .meme-role-info { font-style: italic; color: var(--purple-color); margin-top:0.75rem; display:block; font-weight:600; }


        .results-list { padding-left: 0; list-style-type: none; }
        .results-list li { background-color: var(--light-bg); padding: 1rem; margin-bottom: 0.75rem; border-radius: var(--border-radius); border: 1px solid var(--border-color); }
        .results-list strong { font-weight: 700; color: var(--primary-color); }
        .results-list em { font-style: normal; background-color: #e0e0e0; padding:0.125rem 0.375rem; border-radius:0.25rem; font-size:0.9em;}
        .results-list .meme-player { border-left: 4px solid var(--purple-color); padding-left: calc(1rem - 4px); }
        .results-list .inactive-player { opacity: 0.6; background-color: #e9ecef; text-decoration: line-through; }


        .outcome-message { font-size: 1.2em; font-weight: bold; padding: 1rem; border-radius: var(--border-radius); margin-bottom: 1.25rem; }
        .outcome-win-good { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; } /* Normalos gewinnen */
        .outcome-win-bad { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; } /* Mr. White gewinnt */
        .outcome-neutral { background-color: #e2e3e5; color: #383d41; border: 1px solid #d6d8db; }


        hr.separator { border: 0; height: 1px; background-color: var(--border-color); margin: 2rem 0; }
        .button-group { display: flex; gap: 10px; }
        .button-group .button, .button-group a.button-link { flex-grow: 1; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($spielName); ?></h1>

        <?php if (isset($_SESSION['error_message'])): ?>
            <p class="error"><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></p>
        <?php endif; ?>

        <?php // --- Phase 1: Spieleranzahl auswählen ---
        if ($current_phase == 'setup_spieleranzahl'): ?>
            <h2>Spieleranzahl wählen</h2>
            <form method="POST">
                <input type="hidden" name="action" value="set_spieleranzahl">
                <div class="form-group">
                    <label for="anzahl_spieler">Wie viele spielen mit?</label>
                    <select name="anzahl_spieler" id="anzahl_spieler" required>
                        <?php foreach ($spielerAnzahlOptionen as $anzahl): ?>
                            <option value="<?php echo $anzahl; ?>" <?php echo (($_SESSION['anzahl_spieler'] ?? $spielerAnzahlOptionen[0]) == $anzahl) ? 'selected' : ''; ?>>
                                <?php echo $anzahl; ?> Spieler
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="button">Weiter zu Namen</button>
            </form>
        <?php endif; ?>


        <?php // --- Phase 2: Spielernamen eingeben ---
        if ($current_phase == 'setup_namen' && isset($_SESSION['anzahl_spieler'])): ?>
            <h2>Spielernamen eingeben</h2>
            <form method="POST">
                <input type="hidden" name="action" value="start_initial_game_with_names">
                <?php for ($i = 0; $i < $_SESSION['anzahl_spieler']; $i++): ?>
                    <div class="form-group">
                        <label for="spieler_name_<?php echo $i; ?>">Name Spieler <?php echo $i + 1; ?>:</label>
                        <input type="text" name="spieler_name[<?php echo $i; ?>]" id="spieler_name_<?php echo $i; ?>" required
                               value="<?php echo htmlspecialchars(($_SESSION['original_namen'][$i] ?? '')); ?>">
                    </div>
                <?php endfor; ?>
                <button type="submit" class="button button-green">Spiel starten & Rollen vergeben</button>
            </form>
             <form method="POST" style="margin-top:10px;">
                 <input type="hidden" name="action" value="reset_full_game">
                 <button type="submit" class="button button-secondary" formaction="spiel.php?action=go_to_setup_spieleranzahl">Zurück zur Spieleranzahl</button>
            </form>
            <?php if($action == 'go_to_setup_spieleranzahl') { $_SESSION['game_phase'] = 'setup_spieleranzahl'; echo '<script>window.location.href="spiel.php";</script>'; exit;} ?>
        <?php endif; ?>

        <?php // --- Phase 3a: Rollen ansehen ZWISCHENSCHRITT (Pass the Device) ---
        if ($current_phase == 'rollen_ansehen_zwischenschritt' && isset($_SESSION['spieler_daten'][$aktiverSpielerIndex]) && $_SESSION['spieler_daten'][$aktiverSpielerIndex]['aktiv']):
            $aktuellerSpieler = $_SESSION['spieler_daten'][$aktiverSpielerIndex];
        ?>
            <div class="pass-device-screen">
                <h2>Gerät weitergeben!</h2>
                <p>Bitte gib das Gerät an <strong><?php echo htmlspecialchars($aktuellerSpieler['name']); ?></strong>.</p>
                <p><?php echo htmlspecialchars($aktuellerSpieler['name']); ?>, nur du darfst jetzt auf den Bildschirm schauen!</p>
                <a href="spiel.php?action=show_role_for_player&player_idx=<?php echo $aktiverSpielerIndex; ?>" class="button button-green">
                    Ich bin <?php echo htmlspecialchars($aktuellerSpieler['name']); ?> und bereit, meine Rolle zu sehen!
                </a>
            </div>
        <?php
            if (($action == 'show_role_for_player') && isset($_GET['player_idx']) && $_GET['player_idx'] == $aktiverSpielerIndex) {
                 $_SESSION['game_phase'] = 'rollen_anzeigen';
                 echo '<script>window.location.href = "spiel.php";</script>'; exit;
            }
        endif; ?>

        <?php // --- Phase 3b: Rollen ANZEIGEN ---
        if ($current_phase == 'rollen_anzeigen' && isset($_SESSION['spieler_daten'][$aktiverSpielerIndex]) && $_SESSION['spieler_daten'][$aktiverSpielerIndex]['aktiv']):
            $aktuellerSpieler = $_SESSION['spieler_daten'][$aktiverSpielerIndex];
        ?>
            <div class="role-display">
                <h3><?php echo htmlspecialchars($aktuellerSpieler['name']); ?>, deine geheime(n) Rolle(n):</h3>
                <p>Rolle: <strong><?php echo htmlspecialchars($aktuellerSpieler['angezeigte_rolle']); ?></strong></p>
                <p>Dein Wort: <strong><?php echo htmlspecialchars($aktuellerSpieler['wort']); ?></strong></p>
                <?php if ($aktuellerSpieler['nebenrolle'] == 'Mister Meme'): ?>
                    <p class="meme-role-info">Zusatzrolle: Du bist Mister Meme! Gestalte deine Hinweise besonders kreativ und meme-artig!</p>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="action" value="rolle_gesehen">
                    <input type="hidden" name="spieler_index_gesehen" value="<?php echo $aktiverSpielerIndex; ?>">
                    <button type="submit" class="button button-green">Verstanden! (Bildschirm verdecken & weitergeben)</button>
                </form>
            </div>
        <?php endif; ?>

        <?php // --- Phase 4: Hinweisrunde Info ---
        if ($current_phase == 'hinweisrunde_info'):
            $startSpielerName = $_SESSION['aktueller_runden_start_spieler_name'] ?? "Unbekannt";
        ?>
            <div class="info-box" style="background-color: var(--info-color); color:var(--dark-text); border-color: #0AA1B8;">
                <h2>Hinweisrunde (verbal)</h2>
                <p>Aktive Spieler: <?php echo count($aktiveSpielerFuerAnzeige); ?></p>
                <p>Gebt nun reihum (beginnend mit <strong><?php echo htmlspecialchars($startSpielerName); ?></strong>) einen Hinweis zu eurem Wort. Nur aktive Spieler geben Hinweise!</p>
                <p>Mr. White muss clever improvisieren! Und Mister Meme... sei kreativ!</p>
                <p>Versucht herauszufinden, wer Mr. White ist.</p>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="start_abstimmung_verbal">
                <button type="submit" class="button button-orange">Zur verbalen Abstimmung</button>
            </form>
        <?php endif; ?>

        <?php // --- Phase 5: Verbale Abstimmung ---
        if ($current_phase == 'abstimmung_verbal' && !empty($aktiveSpielerFuerAnzeige)): ?>
            <div class="verbal-vote-box">
                <h2>Verbale Abstimmung</h2>
                <p>Diskutiert in der Gruppe (nur unter den <strong>aktiven</strong> Spielern!), wer Mr. White sein könnte.</p>
                <p>Wählt dann gemeinsam einen Spieler aus der Liste der <strong>aktiven</strong> Spieler aus. Der Spielleiter trägt die Entscheidung ein.</p>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="submit_verbal_vote">
                <div class="form-group">
                    <label for="voted_player_id">Wen wählt die Gruppe raus?</label>
                    <select name="voted_player_id" id="voted_player_id" required>
                        <option value="">-- Aktiven Spieler auswählen --</option>
                        <?php foreach($aktiveSpielerFuerAnzeige as $spieler): ?>
                            <option value="<?php echo htmlspecialchars($spieler['id']); ?>">
                                <?php echo htmlspecialchars($spieler['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="button button-red">Diesen Spieler rauswählen</button>
            </form>
        <?php endif; ?>

        <?php // --- Phase 5X: Mr. White rät ---
        if ($current_phase == 'mr_white_guesst' && isset($_SESSION['letzter_rausgewaehlter_spieler_id'])):
            $mrWhiteName = "";
            // Finde den Namen des Mr. White, der gerade rausgewählt wurde
            foreach($_SESSION['spieler_daten'] as $s) { 
                if($s['id'] == $_SESSION['letzter_rausgewaehlter_spieler_id'] && $s['tatsaechliche_rolle'] == 'Mr. White') {
                    $mrWhiteName = $s['name']; break;
                }
            }
        ?>
            <div class="mr-white-guess-box" style="background-color: #fff3cd; border-color:#ffecb5; color:#664d03;">
                <h2>Mr. Whites letzte Chance!</h2>
                <p><strong><?php echo htmlspecialchars($mrWhiteName); ?></strong>, du wurdest als Mr. White entlarvt!</p>
                <p>Du hast jetzt die Chance, das Hauptwort zu erraten. Wenn du richtig liegst, gewinnst du trotzdem!</p>
                <p>Zur Erinnerung: Das Wort des/der Undercover war(en) "<?php echo htmlspecialchars($_SESSION['aehnliches_wort'] ?? ''); ?>". Das ist <em>nicht</em> das Hauptwort.</p>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="submit_mr_white_guess">
                <div class="form-group">
                    <label for="word_guess">Dein Tipp für das Hauptwort:</label>
                    <input type="text" name="word_guess" id="word_guess" required autofocus>
                </div>
                <button type="submit" class="button button-green">Wort abschicken!</button>
            </form>
        <?php endif; ?>
        
        <?php // --- Phase 5Y: Zwischenauflösung nach Rauswahl / Rateversuch ---
        if ($current_phase == 'zwischen_aufloesung' && isset($_SESSION['letzter_rausgewaehlter_spieler_id'])):
            $rausgewaehlterSpieler = null;
            foreach($_SESSION['spieler_daten'] as $p) { if($p['id'] == $_SESSION['letzter_rausgewaehlter_spieler_id']) {$rausgewaehlterSpieler = $p; break;}}
        ?>
            <div class="partial-reveal-box">
                <h2>Runden-Ergebnis</h2>
                <?php if ($rausgewaehlterSpieler): ?>
                    <p>Rausgewählt wurde: <strong><?php echo htmlspecialchars($rausgewaehlterSpieler['name']); ?></strong>.</p>
                    <p>Seine/Ihre tatsächliche Rolle war: <strong><?php echo htmlspecialchars($rausgewaehlterSpieler['tatsaechliche_rolle']); ?></strong>.</p>
                    <?php if ($rausgewaehlterSpieler['tatsaechliche_rolle'] == 'Mr. White'): ?>
                        <?php if (isset($_SESSION['mr_white_guessed_correctly']) && $_SESSION['mr_white_guessed_correctly']): ?>
                            <p style="color:var(--success-color); font-weight:bold;">Mr. White hat das Hauptwort ("<?php echo htmlspecialchars($_SESSION['haupt_wort']); ?>") KORREKT erraten!</p>
                        <?php elseif (isset($_SESSION['mr_white_guessed_correctly']) && !$_SESSION['mr_white_guessed_correctly']): ?>
                            <p style="color:var(--danger-color); font-weight:bold;">Mr. White hat FALSCH geraten (Tipp: "<?php echo htmlspecialchars($_SESSION['mr_white_guess_word'] ?? ''); ?>").</p>
                        <?php else: // Fallback, falls Rateversuch noch nicht ausgewertet (sollte nicht passieren) ?>
                             <p>Mr. Whites Rateversuch steht noch aus oder wurde nicht korrekt verarbeitet.</p>
                        <?php endif; ?>
                    <?php else: // Ein anderer Spieler wurde rausgewählt ?>
                        <p>Das Spiel geht weiter!</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p>Fehler: Informationen zum rausgewählten Spieler nicht gefunden.</p>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="action" value="continue_after_partial_reveal">
                    <button type="submit" class="button button-purple">Weiter zum Spielende oder nächster Runde</button>
                </form>
            </div>
        <?php endif; ?>


        <?php // --- Phase 6: Endgültige Auflösung ---
        if ($current_phase == 'endgueltige_aufloesung' && isset($_SESSION['spieler_daten'])):
            $gewinnerNachricht = $_SESSION['spiel_gewinner_nachricht'] ?? "Das Spiel ist zu Ende!";
            $message_class = "outcome-neutral";
            if (strpos(strtolower($gewinnerNachricht), "mr. white gewinnt") !== false) {
                $message_class = "outcome-win-bad";
            } elseif (strpos(strtolower($gewinnerNachricht), "gewinnen") !== false) {
                $message_class = "outcome-win-good";
            }
        ?>
            <h2>Endgültige Auflösung!</h2>
            <div class="outcome-message <?php echo $message_class; ?>">
                <?php echo $gewinnerNachricht; ?>
            </div>

            <h3>Ursprüngliche Wörter & Rollen aller Spieler:</h3>
            <p>Das Hauptwort war: <strong><?php echo htmlspecialchars($_SESSION['haupt_wort'] ?? 'N/A'); ?></strong></p>
            <p>Das Wort des/der Undercover war: <strong><?php echo htmlspecialchars($_SESSION['aehnliches_wort'] ?? 'N/A'); ?></strong></p>
            <ul class="results-list">
            <?php foreach ($_SESSION['spieler_daten'] as $spieler): ?>
                <li class="<?php if(!$spieler['aktiv']) echo 'inactive-player'; ?> <?php if($spieler['nebenrolle'] == 'Mister Meme') echo 'meme-player';?>">
                    <strong><?php echo htmlspecialchars($spieler['name']); ?></strong>
                    war <em><?php echo htmlspecialchars($spieler['tatsaechliche_rolle']); ?></em>.
                    Angezeigt wurde: "<?php echo htmlspecialchars($spieler['angezeigte_rolle']); ?>".
                    Wort: "<?php echo htmlspecialchars($spieler['wort']); ?>".
                    <?php if ($spieler['nebenrolle'] == 'Mister Meme') echo " <span class='meme-role-info' style='font-size:0.9em; display:inline;'>(Mister Meme!)</span>"; ?>
                    <?php if (!$spieler['aktiv'] && $spieler['id'] != ($_SESSION['letzter_rausgewaehlter_spieler_id'] ?? null) ) echo " <strong style='color:var(--secondary-color);'>(Zuvor rausgewählt)</strong>"; ?>
                     <?php if (!$spieler['aktiv'] && $spieler['id'] == ($_SESSION['letzter_rausgewaehlter_spieler_id'] ?? null) ) echo " <strong style='color:var(--danger-color);'>(Zuletzt rausgewählt)</strong>"; ?>

                </li>
            <?php endforeach; ?>
            </ul>
            
            <div class="button-group">
                <form method="POST" style="flex-grow:1;">
                    <input type="hidden" name="action" value="next_full_game_same_players">
                    <button type="submit" class="button button-purple">Neues Spiel (gleiche Spieler)</button>
                </form>
                <form method="POST" style="flex-grow:1;">
                    <input type="hidden" name="action" value="reset_full_game">
                    <button type="submit" class="button button-secondary">Komplett Neues Spiel (neue Spieler)</button>
                </form>
            </div>
        <?php endif; ?>

        <?php // Reset-Button auf allen Seiten außer Startseite und Endseite
        if (!in_array($current_phase, ['setup_spieleranzahl', 'endgueltige_aufloesung', 'setup_namen']) && isset($_SESSION['original_namen'])): ?>
            <hr class="separator">
            <form method="POST">
                <input type="hidden" name="action" value="reset_full_game">
                <button type="submit" class="button button-secondary">Aktuelles Spiel komplett abbrechen & Neu starten</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
