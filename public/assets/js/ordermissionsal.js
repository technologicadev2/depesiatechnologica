document.addEventListener("DOMContentLoaded", function () {

    const dataTable = $("#ordermissionsalTable").DataTable({
        dom: '<"row ms-2 me-3"<"col-12 col-md-6"l><"col-12 col-md-6"f>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        displayLength: 10,
        lengthMenu: [10, 25, 50, 100],
        ordering: true,
        responsive: true,
        language: {
            search: "Rechercher:",
            lengthMenu: "Afficher _MENU_",
            info: "Affichage de _START_ à _END_ sur _TOTAL_ éléments",
            paginate: {
                first: "Premier", last: "Dernier", next: "Suivant", previous: "Précédent"
            }
        }
    });

    // ===================== FICHE OFFICIELLE =====================
    $(document).on("click", ".show-fiche", function () {
        const id = $(this).data("id");

        $.ajax({
            url: `${baseUrl}/${id}/fiche`,
            type: "GET",
            success: function (response) {
                if (response.success) {
                    $("#fiche-content").html(response.html);
                    $("#ficheModal").modal("show");
                }
            },
            error: function () {
                Swal.fire("Erreur", "Impossible de charger la fiche", "error");
            }
        });
    });

    // ===================== OUVRIR MODAL MODIFICATION =====================
    $(document).on("click", ".edit-mission", function () {
        const id = $(this).data("id");

        $.ajax({
            url: `${baseUrl}/${id}/edit`,
            type: "GET",
            success: function (response) {
                if (response.success) {
                    const ordre = response.data;

                    $("#edit_id").val(ordre.id);
                    $("#heure_depart").val(ordre.heure_depart ? ordre.heure_depart.substring(0, 5) : '');
                    $("#heure_retour").val(ordre.heure_retour ? ordre.heure_retour.substring(0, 5) : '');
                    $("#frais").val(ordre.frais ?? '');
                    const dateRetour = ordre.date_retour ? ordre.date_retour.split('T')[0].split(' ')[0] : '';
                    $("#date_retour").val(dateRetour);

                    $("#editModal").modal("show");
                }
            },
            error: function () {
                Swal.fire("Erreur", "Impossible de charger les données", "error");
            }
        });
    });

    // ===================== ENREGISTRER MODIFICATION =====================
    $("#btn-save-edit").on("click", function () {
        const id = $("#edit_id").val();

        $.ajax({
            url: `${baseUrl}/${id}/update`,
            type: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
               
                heure_depart: $("#heure_depart").val(),
                heure_retour: $("#heure_retour").val(),
                date_retour: $("#date_retour").val(),
                frais: $("#frais").val(),
            },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Succès',
                        text: response.message,
                        timer: 1500
                    });
                    $("#editModal").modal("hide");
                    location.reload();
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: xhr.responseJSON?.message || 'Une erreur est survenue'
                });
            }
        });
    });



  // ===================== IMPRESSION / EXPORT PDF =====================
$(document).on("click", "#printFicheBtn", function () {
    console.log("Bouton Imprimer cliqué"); // debug

    if (typeof html2pdf === 'undefined') {
        console.error("html2pdf.js n'est pas chargé !");
        Swal.fire("Erreur", "La librairie PDF n'a pas pu être chargée (vérifiez votre connexion ou un bloqueur de pub).", "error");
        return;
    }

    const element = document.getElementById("fiche-printable");
    console.log("Élément trouvé :", element); // debug

    if (!element) {
        Swal.fire("Erreur", "Contenu de la fiche introuvable. Ouvrez d'abord la fiche.", "error");
        return;
    }

    const opt = {
        margin: 0.5,
        filename: 'ordre_de_mission.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save()
        .then(() => console.log("PDF généré avec succès"))
        .catch((err) => {
            console.error("Erreur html2pdf :", err);
            Swal.fire("Erreur", "Erreur lors de la génération du PDF.", "error");
        });
});
    // Reset modal à la fermeture
    $('#editModal').on('hidden.bs.modal', function () {
        $("#edit_id, #heure_depart, #heure_retour, #frais,#date_retour").val('');
    });

}); 