<?php

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'eventmanager');

/**
 * Fonction de connexion à la base de données
 * @return mysqli|false Objet de connexion MySQLi ou false en cas d'erreur
 */
function connectToDatabase() {
    // Connexion à la base de données
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

    // Vérification de la connexion
    if ($conn->connect_error) {
        die("Échec de la connexion à la base de données : " . $conn->connect_error);
        return false;
    }

    return $conn;
}

// Exemple d'utilisation :
$conn = connectToDatabase();
if ($conn) {
    echo "Connexion réussie !";
    // Vous pouvez effectuer d'autres opérations avec $conn ici
} else {
    echo "Échec de la connexion !";
}

?>
