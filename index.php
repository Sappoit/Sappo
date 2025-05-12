<?php
require 'connection.php';

// Inizializza variabili vuote per evitare warning
$Nome = $Cognome = $Username = $Email = $Password = $Indirizzo = $Ntel = "";
$error = "";
$success = "";

if (isset($_POST["submit"])) {
    // Prendi i valori inviati tramite il form
    $Nome = isset($_POST["Nome"]) ? $_POST["Nome"] : "";
    $Cognome = isset($_POST["Cognome"]) ? $_POST["Cognome"] : "";
    $Username = isset($_POST["Username"]) ? $_POST["Username"] : "";
    $Email = isset($_POST["Email"]) ? $_POST["Email"] : "";
    $Password = isset($_POST["Password"]) ? $_POST["Password"] : "";  // Password non hashata
    $Indirizzo = isset($_POST["Indirizzo"]) ? $_POST["Indirizzo"] : "";
    $Ntel = isset($_POST["Ntel"]) ? $_POST["Ntel"] : "";

    // Verifica che tutti i campi siano stati inviati
    if (empty($Username) || empty($Email) || empty($Password) || empty($Indirizzo) || empty($Ntel)) {
        $error = "Per favore, compila tutti i campi richiesti!";
    } else {
        // Prima di inserire, controlla se l'email esiste già
        $check_email = $conn->prepare("SELECT Email FROM clienti WHERE Email = ?");
        $check_email->bind_param("s", $Email);
        $check_email->execute();
        $result = $check_email->get_result();
        
        if ($result->num_rows > 0) {
            // Email già esistente
            $error = "Questo indirizzo email è già registrato. Usa un altro indirizzo email.";
        } else {
            // Controlla anche se lo username esiste già
            $check_username = $conn->prepare("SELECT Username FROM clienti WHERE Username = ?");
            $check_username->bind_param("s", $Username);
            $check_username->execute();
            $result_username = $check_username->get_result();
            
            if ($result_username->num_rows > 0) {
                // Username già esistente
                $error = "Questo username è già in uso. Scegli un altro username.";
            } else {
                // NOTA: Se vuoi implementare l'hashing della password in futuro, inserisci qui:
                // $hashed_password = password_hash($Password, PASSWORD_DEFAULT);
                
                // Prepara la query SQL per inserire i dati (senza hashing password)
                $stmt = $conn->prepare("INSERT INTO clienti (Nome, Cognome, Username, Email, Password, Indirizzo, Ntel) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $Nome, $Cognome, $Username, $Email, $Password, $Indirizzo, $Ntel);
                
                // NOTA: Se usi l'hashing, usa $hashed_password:
                // $stmt->bind_param("sssssss", $Nome, $Cognome, $Username, $Email, $hashed_password, $Indirizzo, $Ntel);

                // Esegui la query con gestione errori
                try {
                    if ($stmt->execute()) {
                        $success = "Registrazione completata con successo!";
                        // Pulisci i campi dopo l'inserimento riuscito
                        $Nome = $Cognome = $Username = $Email = $Password = $Indirizzo = $Ntel = "";
                    } else {
                        $error = "Errore nell'inserimento dei dati: " . $stmt->error;
                    }
                } catch (mysqli_sql_exception $e) {
                    // Cattura errori SQL specifici
                    if ($e->getCode() == 1062) { // Errore di duplicazione
                        if (strpos($e->getMessage(), 'Email') !== false) {
                            $error = "Questo indirizzo email è già registrato.";
                        } elseif (strpos($e->getMessage(), 'Username') !== false) {
                            $error = "Questo username è già in uso.";
                        } else {
                            $error = "C'è già un utente con queste informazioni.";
                        }
                    } else {
                        $error = "Errore del database: " . $e->getMessage();
                    }
                }
                
                $stmt->close();
            }
            $check_username->close();
        }
        $check_email->close();
    }
}

/* NOTA SULLA SICUREZZA:
 * Memorizzare le password in chiaro è una pratica FORTEMENTE sconsigliata per motivi di sicurezza.
 * Per implementare l'hashing della password (RACCOMANDATO):
 * 1. Aggiungi questa riga dopo la validazione:
 *    $hashed_password = password_hash($Password, PASSWORD_DEFAULT);
 * 2. Usa $hashed_password invece di $Password nella query di inserimento
 * 3. Assicurati che il campo Password nel database sia VARCHAR(255) per ospitare l'hash
 * 4. Per verificare la password durante il login, usa:
 *    if (password_verify($password_inserita, $hash_dal_database)) { ... }
 */
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1 id="titolo">Registrazione Cliente</h1>
    
    <?php if(!empty($error)): ?>
        <div style="color: red;"><?php echo $error; ?></div><br>
    <?php endif; ?>
    
    <?php if(!empty($success)): ?>
        <div style="color: green;"><?php echo $success; ?></div><br>
    <?php endif; ?>
    
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" autocomplete="off">
        <label for="Nome">Nome:</label>
        <input type="text" id="Nome" name="Nome" value="<?php echo htmlspecialchars($Nome); ?>" required><br><br>
        
        <label for="Cognome">Cognome:</label>
        <input type="text" id="Cognome" name="Cognome" value="<?php echo htmlspecialchars($Cognome); ?>" required><br><br>
        
        <label for="Username">Username:</label>
        <input type="text" id="Username" name="Username" value="<?php echo htmlspecialchars($Username); ?>" required><br><br>
        
        <label for="Email">Email:</label>
        <input type="email" id="Email" name="Email" value="<?php echo htmlspecialchars($Email); ?>" required><br><br>
        
        <label for="Password">Password:</label>
        <input type="password" id="Password" name="Password" required><br><br>
        
        <label for="Indirizzo">Indirizzo:</label>
        <input type="text" id="Indirizzo" name="Indirizzo" value="<?php echo htmlspecialchars($Indirizzo); ?>" required><br><br>
        
        <label for="Ntel">Numero di telefono:</label>
        <input type="tel" id="Ntel" name="Ntel" value="<?php echo htmlspecialchars($Ntel); ?>" required><br><br>
        
        <input type="submit" name="submit" value="Registrati">
    </form>
</body>
</html>