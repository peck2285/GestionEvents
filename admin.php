<?php
include('header.php');
include('config.php');

// READ Reservations
$sql_read_reservations = "SELECT reserve.reserve_id, reserve.name, party.dates, users.username 
                          FROM reserve 
                          INNER JOIN party ON reserve.event_id = party.event_id 
                          INNER JOIN users ON reserve.user_id = users.user_id";
$result_reservations = $conn->query($sql_read_reservations);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/css/party.css">
    <title>Gestion des Evènements</title>
</head>

<body>
    <main>
        <section class="welcome-section">
            <h2>Bienvenue sur la page d'administration</h2>
            <!-- Affichage des réservations -->
            <h3>Liste des Réservations</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Date de l'événement</th>
                        <th>Utilisateur</th>
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
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="4">Aucune réservation trouvée.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </section>
    </main>
</body>

</html>

<?php include('footer.php'); ?>