<?php
include('header.php');
include('config.php');

// Vérification de l'existence de l'ID de l'événement dans l'URL
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    echo '<p>Identifiant d\'événement invalide.</p>';
    exit(); // Arrêter le script si l'ID de l'événement est invalide
}

// Récupération de l'ID de l'événement depuis l'URL
$event_id = $_GET['event_id'];

// Vérification de l'existence de l'événement dans la base de données
$sql_check_event = "SELECT * FROM party WHERE event_id = ?";
$stmt_check_event = $conn->prepare($sql_check_event);
$stmt_check_event->bind_param("i", $event_id);
$stmt_check_event->execute();
$result_check_event = $stmt_check_event->get_result();

if ($result_check_event->num_rows == 0) {
    echo '<p>L\'événement spécifié n\'existe pas.</p>';
    exit(); // Arrêter le script si l'événement n'existe pas
}

// Création automatique d'une réservation (si elle n'existe pas déjà)
$name = ''; // Initialiser le nom de la réservation
$status = 'En attente'; // statut par défaut

// Récupérer le titre de l'événement pour le nom de la réservation
$row_event = $result_check_event->fetch_assoc();
$name = $row_event['name'];

// Récupérer l'ID de l'utilisateur (utilisateur actuellement connecté)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Vérifier si une réservation existe déjà pour cet événement et cet utilisateur
$sql_check_reservation = "SELECT COUNT(*) as count FROM reserve WHERE name = ? AND event_id = ? AND user_id = ?";
$stmt_check_reservation = $conn->prepare($sql_check_reservation);
$stmt_check_reservation->bind_param("sii", $name, $event_id, $user_id);
$stmt_check_reservation->execute();
$result_check_reservation = $stmt_check_reservation->get_result();
$row_check_reservation = $result_check_reservation->fetch_assoc();

if ($row_check_reservation['count'] == 0) {
    // Insertion de la réservation dans la base de données
    $sql_create_reservation = $conn->prepare("INSERT INTO reserve (name, event_id, user_id, status, createdAt, updatedAt) VALUES (?, ?, ?, ?, current_timestamp(), current_timestamp())");
    $sql_create_reservation->bind_param("siss", $name, $event_id, $user_id, $status);

    if ($sql_create_reservation->execute()) {
        echo '<p>Réservation créée avec succès.</p>';
    } else {
        echo '<p>Erreur lors de la création de la réservation : ' . $conn->error . '</p>';
    }

    $sql_create_reservation->close();
}

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reserve'])) {
    $reserve_id_to_delete = $_POST['reserve_id_to_delete'];
    // Supprimer les tâches liées au projet
    $sql_delete_reserve = "DELETE FROM reserve WHERE reserve_id = $reserve_id_to_delete";
    if ($conn->query($sql_delete_reserve) === TRUE) {
        // echo '<p>Tâches du projet supprimées avec succès.</p>';
    } else {
        echo '<p>Erreur lors de la suppression des réservations d\'un évènement: ' . $conn->error . '</p>';
    }
}


// Lecture des réservations associées à cet événement
$sql_read_reservations = "SELECT reserve.reserve_id, reserve.name, party.dates, users.username FROM reserve 
                         INNER JOIN party ON reserve.event_id = party.event_id
                         INNER JOIN users ON reserve.user_id = users.user_id
                         WHERE reserve.event_id = ?";
$stmt_read_reservations = $conn->prepare($sql_read_reservations);
$stmt_read_reservations->bind_param("i", $event_id);
$stmt_read_reservations->execute();
$result_reservations = $stmt_read_reservations->get_result();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/party.css">
    <title>Liste des Réservations</title>
</head>

<body>
    <main>
        <h1>Liste des Réservations pour l'événement "<?php echo $row_event['name']; ?>"</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Date de l'événement</th>
                    <th>Nom de l'utilisateur</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result_reservations->num_rows > 0) {
                    while ($row_reservation = $result_reservations->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $row_reservation['reserve_id'] . '</td>';
                        echo '<td>' . $row_reservation['name'] . '</td>';
                        echo '<td>' . $row_reservation['dates'] . '</td>';
                        echo '<td>' . $row_reservation['username'] . '</td>';
                        echo '<td><button onclick="confirmDelete(' . $row_reservation['reserve_id'] . ')">Supprimer</button></td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5">Aucune réservation trouvée pour cet événement.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </main>

    <!-- Script pour gérer la suppression -->
    <script>
        function confirmDelete(reservationId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?')) {
                document.getElementById('delete_reserve_form').reserve_id_to_delete.value = reservationId;
                document.getElementById('delete_reserve_form').submit();
            }
        }
    </script>

    <!-- Formulaire caché pour la suppression de projet -->
    <form id="delete_reserve_form" method="POST" action="">
        <input type="hidden" name="delete_reserve" value="1">
        <input type="hidden" name="reserve_id_to_delete" value="">
    </form>

    <?php include('footer.php'); ?>