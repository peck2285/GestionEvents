<?php
include('header.php');
include('config.php');

// CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_party'])) {
    $name = htmlspecialchars($_POST['name']);
    $description = htmlspecialchars($_POST['description']);
    $datetime = htmlspecialchars($_POST['datetime']); // Modified to datetime

    // Validation des données (à adapter selon vos besoins)
    if (empty($name) || empty($description) || empty($datetime)) {
        echo '<p>Veuillez remplir tous les champs.</p>';
    } else {
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

        // Vérifier si le nom du projet existe déjà
        $sql_check_duplicate = "SELECT COUNT(*) as count FROM party WHERE name = ?";
        $stmt_check_duplicate = $conn->prepare($sql_check_duplicate);
        $stmt_check_duplicate->bind_param("s", $name);
        $stmt_check_duplicate->execute();
        $result_check_duplicate = $stmt_check_duplicate->get_result();
        $row_check_duplicate = $result_check_duplicate->fetch_assoc();

        if ($row_check_duplicate['count'] > 0) {
            echo '<p>Le nom de l\'évènement existe déjà. Veuillez choisir un nom unique.</p>';
        } else {
            // Utilisation d'une requête préparée pour éviter l'injection SQL
            $sql_create_party = $conn->prepare("INSERT INTO party (name, description, dates, user_id) VALUES (?, ?, ?, ?)");
            $sql_create_party->bind_param("sssi", $name, $description, $datetime, $user_id); // Utilisation de $datetime

            if ($sql_create_party->execute()) {
                echo '<p>Evènement ajouté avec succès.</p>';
            } else {
                echo '<p>Erreur lors de l\'ajout de l\'évènement projet : ' . $conn->error . '</p>';
            }

            $sql_create_party->close();
        }

        $stmt_check_duplicate->close();
    }
}


// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_party'])) {
    $party_id_to_delete = $_POST['party_id_to_delete'];
    // Supprimer les tâches liées au projet
    $sql_delete_reserve = "DELETE FROM reserve WHERE event_id = $party_id_to_delete";
    if ($conn->query($sql_delete_reserve) === TRUE) {
        // echo '<p>Tâches du projet supprimées avec succès.</p>';
    } else {
        echo '<p>Erreur lors de la suppression des réservations d\'un évènement: ' . $conn->error . '</p>';
    }

    // Enfin, supprimer le projet lui-même
    $sql_delete_party = "DELETE FROM party WHERE event_id = $party_id_to_delete";
    if ($conn->query($sql_delete_party) === TRUE) {
        echo '<p>Evènement supprimé avec succès.</p>';
    } else {
        echo '<p>Erreur lors de la suppression d\'un évènement : ' . $conn->error . '</p>';
    }
}


// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_party'])) {
    $party_id_to_update = $_POST['edit_party_id'];
    $updated_name = htmlspecialchars($_POST['edit_name']);
    $updated_description = htmlspecialchars($_POST['edit_description']);
    $updated_datetime = htmlspecialchars($_POST['edit_datetime']); // Modified to datetime

    // Validation des données (à adapter selon vos besoins)
    if (!empty($party_id_to_update) && is_numeric($party_id_to_update) && !empty($updated_name) && !empty($updated_description)) {
        $sql_update_party = "UPDATE party SET name = '$updated_name', description = '$updated_description', dates = '$updated_datetime' WHERE event_id = $party_id_to_update"; // Corrected dates to datetime

        if ($conn->query($sql_update_party) === TRUE) {
            echo '<p>Evènement mis à jour avec succès.</p>';
        } else {
            echo '<p>Erreur lors de la mise à jour de l\'évènement : ' . $conn->error . '</p>';
        }
    } else {
        echo '<p>Données de mise à jour invalides.</p>';
    }
}

// READ
$user_loged = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$sql_read_party = "SELECT * FROM party";
$result_party = $conn->query($sql_read_party);

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
        <!-- Bouton pour afficher le formulaire d'ajout dans un modal -->
        <button onclick="openAddModal()">Ajouter</button>

        <!-- Modal d'ajout de projet -->
        <div id="addModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeAddModal()">&times;</span>
                <h2>Ajouter un Evènement</h2>
                <form method="POST" action="">
                    <label for="name">Nom:</label>
                    <input type="text" name="name" required>

                    <label for="description">Description:</label>
                    <textarea name="description" required></textarea>

                    <label for="datetime">Date et Heure:</label> <!-- Changed label name to Date and Time -->
                    <input type="datetime-local" id="datetime" name="datetime" required> <!-- Changed input type to datetime-local -->

                    <button type="submit" name="create_party">Ajouter</button>
                </form>
            </div>
        </div>

        <!-- Modal d'édition de projet -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditModal()">&times;</span>
                <h2>Modifier Evènement</h2>
                <form method="POST" action="">
                    <input type="hidden" id="edit_party_id" name="edit_party_id" value="">
                    <label for="edit_name">Nom du Projet:</label>
                    <input type="text" id="edit_name" name="edit_name" required>

                    <label for="edit_datetime">Date et Heure:</label> <!-- Changed label name to Date and Time -->
                    <input type="datetime-local" id="edit_datetime" name="edit_datetime" required> <!-- Changed input type to datetime-local -->

                    <label for="edit_description">Description:</label>
                    <textarea id="edit_description" name="edit_description" required></textarea> <!-- Closing tag added here -->

                    <button type="submit" name="update_party">
                        Mettre à Jour</button>
                </form>
            </div>
        </div>

        <!-- Tableau pour afficher la liste des projets -->
        <h2>Liste des Evènements</h2>
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Description</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result_party->num_rows > 0) {
                    while ($row_party = $result_party->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $row_party['name'] . '</td>';
                        echo '<td>' . $row_party['description'] . '</td>';
                        echo '<td>' . $row_party['dates'] . '</td>'; // Change to dates
                        echo '<td>';
                        echo '<button onclick="openEditModal(' . $row_party['event_id'] . ', \'' . $row_party['name'] . '\', \'' . $row_party['description'] . '\', \'' . $row_party['dates'] . '\')">Edit</button>'; // Change to dates
                        echo '<button onclick="confirmDelete(' . $row_party['event_id'] . ')">Delete</button>';
                        echo '<a href="reserve.php?event_id=' . $row_party['event_id'] . '"><button>Réserver</button></a>';
                        echo '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="3">Aucun évènement trouvé.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </main>

    <!-- Script pour gérer les modals et la suppression -->
    <script>
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function openEditModal(partyId, name, description, dates) { // Change to dates
            document.getElementById('edit_party_id').value = partyId;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_datetime').value = dates; // Change to dates
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function confirmDelete(partyId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cet evènement ?')) {
                document.getElementById('delete_party_form').party_id_to_delete.value = partyId;
                document.getElementById('delete_party_form').submit();
            }
        }
    </script>

    <!-- Formulaire caché pour la suppression de projet -->
    <form id="delete_party_form" method="POST" action="">
        <input type="hidden" name="delete_party" value="1">
        <input type="hidden" name="party_id_to_delete" value="">
    </form>

    <?php include('footer.php'); ?>
</body>

</html>