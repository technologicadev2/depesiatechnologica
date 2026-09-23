// function deleteNotification(element, event) {
//     event.preventDefault(); // Empêche le stretched-link de déclencher un clic
//     event.stopPropagation(); // Empêche la propagation de l'événement

//     const notificationId = element.getAttribute("data-notification-id");
//     if (!notificationId) {
//         console.error("Notification ID not found");
//         alert("Erreur : ID de notification non trouvé");
//         return;
//     }

//     if (confirm("Voulez-vous supprimer cette notification ?")) {
//         fetch(`/notifications/${notificationId}`, {
//             method: "DELETE",
//             headers: {
//                 "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
//                 "Accept": "application/json",
//             },
//         })
//             .then((response) => {
//                 console.log("Response Status:", response.status); // Débogage
//                 if (!response.ok) {
//                     throw new Error(`Erreur HTTP : ${response.status}`);
//                 }
//                 return response.json();
//             })
//             .then((data) => {
//                 console.log("Response Data:", data); // Débogage
//                 if (data.success) {
//                     const listItem = element.closest(".dropdown-notifications-item");
//                     listItem.remove(); // Supprime l'élément de la liste

//                     // Met à jour le badge
//                     const badge = document.querySelector(".badge-notifications");
//                     const currentCount = parseInt(badge?.textContent) || 0;
//                     if (currentCount > 1) {
//                         badge.textContent = currentCount - 1;
//                     } else {
//                         badge?.remove();
//                     }

//                     // Vérifie s'il reste des notifications
//                     const notificationList = document.querySelector(".dropdown-notifications-list ul");
//                     if (!notificationList.querySelector(".dropdown-notifications-item")) {
//                         notificationList.innerHTML = `
//                             <li class="list-group-item text-center py-4">
//                                 <i class="bx bx-bell-off bx-lg text-muted mb-3"></i>
//                                 <p class="text-muted mb-0">Aucune nouvelle notification</p>
//                             </li>
//                         `;
//                         document.querySelector(".dropdown-menu-footer")?.remove();
//                         document.querySelector(".dropdown-notifications-all")?.remove();
//                     }
//                 } else {
//                     alert(data.message || "Erreur lors de la suppression de la notification");
//                 }
//             })
//             .catch((error) => {
//                 console.error("Erreur AJAX:", error);
//                 alert("Une erreur s'est produite : " + error.message);
//             });
//     }
// }
function deleteAllNotifications() {
    if (confirm("Voulez-vous supprimer toutes les notifications ?")) {
        fetch('/notifications/delete-all', {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                Accept: "application/json",
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (data.success) {
                    const notificationList = document.querySelector(".dropdown-notifications-list ul");
                    notificationList.innerHTML = `
                        <li class="list-group-item text-center py-4">
                            <i class="bx bx-bell-off bx-lg text-muted mb-3"></i>
                            <p class="text-muted mb-0">Aucune nouvelle notification</p>
                        </li>
                    `;
                    document.querySelector(".badge-notifications")?.remove();
                    document.querySelector(".dropdown-menu-footer")?.remove();
                    document.querySelector(".dropdown-notifications-all")?.remove();
                } else {
                    alert(data.message || "Erreur lors de la suppression");
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                alert("Une erreur s'est produite lors de la suppression");
            });
    }
}