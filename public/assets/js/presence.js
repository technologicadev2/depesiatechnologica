"use strict";
const successSound = new Audio("/assets/audio/success.mp3");
const errorSound = new Audio("/assets/audio/error.mp3");
let direction = "ltr";
if (document.documentElement.classList.contains("rtl")) {
    direction = "rtl";
}

document.addEventListener("DOMContentLoaded", function () {
    try {
        // Sélection des éléments DOM
        const calendarEl = document.getElementById("calendar"),
            appCalendarSidebar = document.querySelector(".app-calendar-sidebar"),
            addEventSidebar = document.getElementById("addEventSidebar"),
            appOverlay = document.querySelector(".app-overlay"),
            availableColors = ["primary", "success", "danger", "warning", "info", "secondary"],
            calendarsColor = window.calendarsColor || {},
            offcanvasTitle = document.querySelector(".offcanvas-title"),
            projectFilter = $("#projectFilter"),
            monthFilter = $("#monthFilter"),
            inlineCalendar = document.querySelector(".inline-calendar"),
            employeeListContainer = document.querySelector("#employeeListContainer"),
            employeeList = document.querySelector("#employeeList"),
            employeeListTitle = document.querySelector("#employeeListTitle"), 
            printFrame = document.getElementById("printFrame");

        // Vérification des éléments DOM
        if (!calendarEl) console.error('Élément #calendar non trouvé');
        if (!addEventSidebar) console.error('Élément #addEventSidebar non trouvé');
        if (!projectFilter.length) console.error('Élément #projectFilter non trouvé');
        if (!monthFilter.length) console.error('Élément #monthFilter non trouvé');
        if (!printFrame) console.error('Élément #printFrame non trouvé');

        let currentEvents = window.currentEvents || [],
            inlineCalInstance,
            calendar,
            currentView = localStorage.getItem("calendarView") || "dayGridMonth";

        const bsAddEventSidebar = new bootstrap.Offcanvas(addEventSidebar);

        // Fonction toast pour notifications
        function showToast(message, type = "info") {
            const toastEl = document.createElement('div');
            toastEl.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            toastEl.style.zIndex = 1055;
            toastEl.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(toastEl);
            setTimeout(() => toastEl.remove(), 3000);
        }

        // Initialisation du sélecteur de mois avec Select2
        if (monthFilter.length) {
            console.log("Locale Moment.js active avant initialisation :", moment.locale());
            if (moment.locale() !== 'fr') {
                console.warn("Locale française non chargée, tentative de chargement...");
                moment.locale('fr');
            }

            const months = [];
            const currentDate = moment();
            // Générer les 12 derniers mois
            for (let i = 0; i < 12; i++) {
                const month = moment(currentDate).subtract(i, "months");
                months.push({
                    value: month.format("YYYY-MM"),
                    text: month.format("MMMM YYYY").charAt(0).toUpperCase() + month.format("MMMM YYYY").slice(1),
                });
            }

            // Ajouter les options au sélecteur
            monthFilter.empty();
            monthFilter.append(`<option value="">Sélectionner un mois</option>`);
            months.forEach((month) => {
                monthFilter.append(`<option value="${month.value}">${month.text}</option>`);
            });

            // Configurer Select2
            monthFilter.select2({
                placeholder: "Sélectionner un mois",
                allowClear: false,
                width: "100%",
                dropdownCssClass: "select2-dropdown-modern",
                selectionCssClass: "select2-selection-modern",
                templateResult: function (option) {
                    if (!option.id) return option.text;
                    return $(`<span><i class="bx bx-calendar me-2"></i>${option.text}</span>`);
                },
                templateSelection: function (option) {
                    if (!option.id) return option.text;
                    return $(`<span><i class="bx bx-calendar me-1"></i>${option.text}</span>`);
                },
            }).on('select2:open', () => console.log('Select2 monthFilter ouvert'));

            // Définir la valeur par défaut au mois courant
            const currentMonth = moment().format("YYYY-MM");
            monthFilter.val(currentMonth).trigger("change.select2");
            console.log("📅 Initialisation monthFilter avec le mois courant:", currentMonth);
        }

        // Gestion du changement de mois dans monthFilter
        let isProgrammaticChange = false; // Flag to prevent event loop
        monthFilter.on("change", function () {
            if (isProgrammaticChange) {
                console.log("📅 Changement programmatique ignoré");
                return;
            }
            const selectedMonth = monthFilter.val();
            console.log("📅 Mois sélectionné via monthFilter:", selectedMonth);
            if (calendar && selectedMonth) {
                isProgrammaticChange = true;
                calendar.gotoDate(`${selectedMonth}-01`);
                calendar.refetchEvents();
                setTimeout(() => { isProgrammaticChange = false; }, 100);
            }
        });

        // Configuration Select2 pour le filtre de projets
        if (projectFilter.length) {
            function renderProjectBadges(option) {
                if (!option.id) return option.text;
                const labelColor = $(option.element).data("label") || "primary";
                const count = $(option.element).data("count") || 0;
                return `
                    <div class="d-flex align-items-center">
                        <span class='badge badge-dot bg-${labelColor} me-2'></span>
                        <span class="fw-medium">${option.text}</span>
                        ${count > 0 ? `<span class="badge bg-light text-muted ms-auto">${count}</span>` : ""}
                    </div>`;
            }

            projectFilter.select2({
                placeholder: "🔍 Filtrer par projets...",
                closeOnSelect: false,
                allowClear: true,
                width: "100%",
                templateResult: renderProjectBadges,
                templateSelection: function (option) {
                    if (!option.id) return option.text;
                    const labelColor = $(option.element).data("label") || "primary";
                    return `<span class='badge bg-${labelColor} text-white'>${option.text}</span>`;
                },
                escapeMarkup: markup => markup,
                dropdownCssClass: "select2-dropdown-modern",
                selectionCssClass: "select2-selection-modern",
            }).on('select2:open', () => console.log('Select2 projectFilter ouvert'));

            projectFilter.on("change", function () {
                const selectedProjects = projectFilter.val() || [];
                console.log("🎯 Filtrage par projets:", selectedProjects);
                calendarEl.style.opacity = "0.7";
                calendarEl.style.transition = "opacity 0.3s ease";
                setTimeout(() => {
                    if (calendar) calendar.refetchEvents();
                    calendarEl.style.opacity = "1";
                    const count = selectedProjects.length;
                    showToast(
                        count === 0 ? "📋 Tous les projets affichés" :
                            `📊 ${count} projet${count > 1 ? "s" : ""} sélectionné${count > 1 ? "s" : ""}`,
                        count === 0 ? "info" : "success"
                    );
                }, 150);
            });
        }

        // Configuration calendrier inline
        if (inlineCalendar) {
            inlineCalInstance = inlineCalendar.flatpickr({
                monthSelectorType: "static",
                inline: true,
                locale: "fr",
                dateFormat: "Y-m-d",
                showMonths: 1,
                enableTime: false,
                onChange: function (selectedDates) {
                    if (selectedDates.length > 0 && calendar) {
                        const selectedDate = moment(selectedDates[0]).format("YYYY-MM-DD");
                        calendar.gotoDate(selectedDate);
                        if (window.innerWidth < 992 && appCalendarSidebar && appOverlay) {
                            appCalendarSidebar.style.transform = "translateX(-100%)";
                            setTimeout(() => {
                                appCalendarSidebar.classList.remove("show");
                                appOverlay.classList.remove("show");
                            }, 300);
                        }
                    }
                },
            });
        }

        // Fonction pour afficher la liste des employés
        function showEmployeeList(projectId, date, status) {
            if (!employeeListContainer || !employeeList || !employeeListTitle) {
                console.error('Éléments employeeListContainer, employeeList ou employeeListTitle non trouvés');
                return;
            }

            employeeListContainer.classList.remove("d-none");
            const statusText = status === "1" ? "Présents" : "Absents";
            const statusIcon = status === "1" ? "✅" : "❌";
            const statusColor = status === "1" ? "success" : "danger";

            offcanvasTitle.innerHTML = `${statusIcon} Salariés ${statusText}`;
            employeeListTitle.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="badge bg-${statusColor} me-2">${statusIcon}</div>
                    <div>
                        <div class="fw-bold">Salariés ${statusText.toLowerCase()}</div>
                        <small class="text-muted">${moment(date).format("dddd D MMMM YYYY")}</small>
                    </div>
                </div>
            `;

            employeeList.innerHTML = `
                <li class="list-group-item text-center py-4">
                    <div class="spinner-grow spinner-grow-sm text-${statusColor} me-2"></div>
                    <div class="spinner-grow spinner-grow-sm text-${statusColor} me-2"></div>
                    <div class="spinner-grow spinner-grow-sm text-${statusColor}"></div>
                    <div class="mt-2 text-muted">Chargement...</div>
                </li>`;

            $.ajax({
                url: window.presenceEmployeesRoute,
                method: "GET",
                data: { project_id: projectId, date, status },
                timeout: 10000,
                success: function (response) {
                    employeeList.innerHTML = "";
                    if (response.employees && response.employees.length > 0) {
                        response.employees.forEach((employee, index) => {
                            const listItem = document.createElement("li");
                            listItem.className = "list-group-item border-0 px-0 py-3";
                            listItem.style.animationDelay = `${index * 0.1}s`;
                            listItem.classList.add("fade-in-up");

                            const initials = `${employee.prenom.charAt(0)}${employee.nom.charAt(0)}`;
const isConge = status === '0' && employee.type_absence === 'c'; // ← vérifier congé
const avatarColor = status === "1" ? "success" : (isConge ? "warning" : "danger"); 
                            // Dans la fonction showEmployeeList, modifiez la création du bouton toggle :
listItem.innerHTML = `
    <div class="d-flex align-items-center">
        <div class="avatar avatar-md me-3 position-relative">
            <span class="avatar-initial rounded-circle bg-label-${avatarColor} fw-bold">
                ${initials}
            </span>
            <span class="avatar-badge bg-${avatarColor}"></span>
        </div>
        <div class="flex-grow-1">
            <h6 class="mb-0 fw-semibold">${employee.prenom} ${employee.nom}</h6>
            ${
                employee.poste
                    ? `<small class="text-muted d-block"><i class="bx bx-briefcase me-1"></i>${employee.poste}</small>`
                    : ""
            }
            ${
                employee.email
                    ? `<small class="text-muted d-block"><i class="bx bx-envelope me-1"></i>${employee.email}</small>`
                    : ""
            }
        </div>
        <div class="text-end">
            <button class="btn btn-sm toggle-status-btn" 
                data-salarie-id="${employee.id}"
                data-project-id="${projectId}" 
                data-date="${date}" 
                data-current-status="${status}"
                title="Changer le statut: ${status === '1' ? 'Marquer absent' : 'Marquer présent'}">
               <i class="bx ${status === '1' ? 'bx-user-check text-success' : (isConge ? 'bx-time text-warning' : 'bx-user-x text-danger')}"></i>
<span class="ms-1" style="${isConge ? 'color: #e67e22; font-weight: bold;' : ''}">
    ${status === '1' ? 'Présent' : (isConge ? 'En Congé' : 'Absent')}
</span>
            </button>
        </div>
    </div>`;
                            employeeList.appendChild(listItem);
                        });

                        const summaryItem = document.createElement("li");
                        summaryItem.className = "list-group-item bg-light text-center border-0 mt-2";
                        summaryItem.innerHTML = `
                            <div class="d-flex justify-content-center align-items-center">
                                <span class="badge bg-${statusColor} me-2">${response.employees.length}</span>
                                <small class="text-muted">salarié${response.employees.length > 1 ? "s" : ""} ${statusText.toLowerCase()}</small>
                            </div>`;
                        employeeList.appendChild(summaryItem);
                    } else {
                        employeeList.innerHTML = `
                            <li class="list-group-item text-center py-5 border-0">
                                <div class="text-muted mb-2">
                                    <i class="bx bx-user-x display-4"></i>
                                </div>
                                <h6 class="text-muted">Aucun salarié ${statusText.toLowerCase()}</h6>
                                <small class="text-muted">pour cette date</small>
                            </li>`;
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Erreur AJAX:', { status, error, response: xhr.responseText });
                    employeeList.innerHTML = `
                        <li class="list-group-item text-center py-5 border-0">
                            <div class="text-danger mb-2">
                                <i class="bx bx-error-circle display-4"></i>
                            </div>
                            <h6 class="text-danger">Erreur de chargement</h6>
                            <small class="text-muted">Impossible de récupérer les données</small>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                                    <i class="bx bx-refresh me-1"></i>Actualiser
                                </button>
                            </div>
                        </li>`;
                },
            });

            bsAddEventSidebar.show();
        }

        // Modifier le bouton toggle
        function modifyToggler() {
            const fcSidebarToggleButton = document.querySelector(".fc-sidebarToggle-button");
            if (fcSidebarToggleButton) {
                fcSidebarToggleButton.classList.remove("fc-button-primary");
                fcSidebarToggleButton.classList.add("d-lg-none", "d-inline-block", "ps-0", "btn-modern-toggle");
                fcSidebarToggleButton.innerHTML = `
                    <div class="hamburger-menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>`;
                fcSidebarToggleButton.setAttribute("data-bs-toggle", "sidebar");
                fcSidebarToggleButton.setAttribute("data-overlay", "");
                fcSidebarToggleButton.setAttribute("data-target", "#app-calendar-sidebar");
            }
        }

        // Obtenir calendriers sélectionnés
        function selectedCalendars() {
            if (!projectFilter.length) return ["all"];
            let selected = projectFilter.val() || [];
            console.log("📊 Calendriers sélectionnés:", selected);
            return selected.length === 0 ? ["all"] : selected;
        }

        // Récupérer événements avec filtre par mois
        function fetchEvents(info, successCallback, failureCallback) {
            try {
                const calendars = selectedCalendars();
                console.log('🔄 Filtrage événements pour:', { calendars });

                let selectedEvents = currentEvents;

                // Filtrer par projets si nécessaire, mais conserver les jours fériés
                if (!calendars.includes('all') && calendars.length > 0) {
                    selectedEvents = currentEvents.filter(event =>
                        calendars.includes(event.extendedProps?.calendar) || event.extendedProps?.isHoliday
                    );
                }

                // Filtrer par la plage de dates visible du calendrier
                const startDate = moment(info.start).format('YYYY-MM-DD');
                const endDate = moment(info.end).format('YYYY-MM-DD');
                selectedEvents = selectedEvents.filter(event => {
                    const eventDate = moment(event.start).format('YYYY-MM-DD');
                    return eventDate >= startDate && eventDate <= endDate;
                });

                console.log(`✅ ${selectedEvents.length} événements filtrés sur ${currentEvents.length} total`);
                successCallback(selectedEvents);
            } catch (error) {
                console.error('❌ Erreur lors du filtrage:', error);
                if (failureCallback) failureCallback(error);
            }
        }

        // Générer le HTML pour l'impression
     function generatePrintHTML(response, selectedMonth) {
    let logoUrl = companySettings && companySettings.logo
        ? `${window.location.origin}/storage/${companySettings.logo}`
        : `${window.location.origin}/assets/img/favicon/anassi2.jpg`;
    let companyName = response.company && response.company.nom_etreprise
        ? response.company.nom_etreprise
        : 'Nom de l\'entreprise';

    const joursNoms = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];

    return `
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                @page { size: A4 landscape; margin: 10mm; }
                body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 0; background: white; }
                .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
                .company-info { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
                .company-logo { flex: 1; text-align: left; max-width: 200px; }
                .company-logo img { max-height: 60px; max-width: 150px; display: block; margin-bottom: 5px; }
                .title-section { flex: 2; text-align: center; }
                .year-section { flex: 1; text-align: right; }
                .main-title { font-size: 16px; font-weight: bold; text-decoration: underline; margin-bottom: 5px; }
                .company-name { font-weight: bold; font-size: 12px; color: #333; margin-top: 5px; }
                .year { font-size: 14px; font-weight: bold; }
                .month-section { text-align: center; margin: 10px 0; font-size: 12px; font-weight: bold; }
                table { width: 100%; border-collapse: collapse; font-size: 9px; margin-top: 10px; }
                th, td { border: 1px solid #000; padding: 3px; text-align: center; vertical-align: middle; }
                th { background-color: #f0f0f0; font-weight: bold; font-size: 8px; }
                .matricule { text-align: left; font-weight: bold; padding-left: 5px; min-width: 80px; }
                .employee-name { text-align: left; font-weight: bold; padding-left: 5px; min-width: 120px; }
                .fonction { text-align: left; padding-left: 5px; min-width: 100px; }
                .day-cell { width: 20px; font-weight: bold; }
                .day-name-cell { font-size: 7px; font-weight: bold; }
                .total-cell { background-color: #e8f4f8; font-weight: bold; }
                .weekend { background-color: #ffff99; }
                .holiday { background-color: #90ee90; }
                .holiday-header { background-color: #90ee90; color: #000; font-weight: bold; }
                .weekend-header { background-color: #ffff99; color: #000; font-weight: bold; }
                .present-mark { color: #0066cc; font-weight: bold; }
                .footer { position: fixed; bottom: 10mm; width: 100%; text-align: center; font-size: 12px; line-height: 1.5; }
                .footer img { max-height: 40px; vertical-align: middle; margin-right: 10px; }
                .footer-company-name { font-weight: bold; color: #333; }
                
                @media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } .footer { position: fixed; bottom: 0; width: 100%; } }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="company-info">
                    <div class="company-logo">
                        <img src="${logoUrl}" alt="Logo entreprise" onerror="this.style.display='none';" />
                        <div class="company-name">${companyName}</div>
                    </div>
                    <div class="title-section">
                        <div class="main-title">FEUILLE DE POINTAGE DE PERSONNEL</div>
                    </div>
                    <div class="year-section">
                        <div class="year">Année ${moment(selectedMonth).format("YYYY")}</div>
                    </div>
                </div>
                <div class="month-section">
                    Mois : ${response.month.toUpperCase()}
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th rowspan="3" class="matricule">Matricule</th>
                        <th rowspan="3" class="employee-name">Les noms des personnel</th>
                        <th rowspan="3" class="fonction">Fonction</th>
                        <th colspan="${response.daysInMonth}">${response.month.toUpperCase()}</th>
                        <th rowspan="3" class="total-cell">Total les jours</th>
                    </tr>
                    <tr>
                        ${Array.from({ length: response.daysInMonth }, (_, day) => {
                            const currentDateOfMonth = moment(selectedMonth + '-' + String(day + 1).padStart(2, '0'));
                            const isWeekend = currentDateOfMonth.day() === 0;
                            let isHoliday = false;
                            let holidayName = '';
                            if (response.holidays) {
                                response.holidays.forEach(holiday => {
                                    const holidayStart = moment(holiday.start);
                                    const holidayEnd = moment(holiday.end);
                                    if (currentDateOfMonth.isBetween(holidayStart, holidayEnd, 'day', '[]')) {
                                        isHoliday = true;
                                        holidayName = holiday.name;
                                    }
                                });
                            }
                            const classes = [];
                            if (isHoliday) classes.push('holiday-header');
                            else if (isWeekend) classes.push('weekend-header');
                            return `<th class="day-cell ${classes.join(' ')}">${day + 1}</th>`;
                        }).join('')}
                    </tr>
                    <tr>
                        ${Array.from({ length: response.daysInMonth }, (_, day) => {
                            const currentDateOfMonth = moment(selectedMonth + '-' + String(day + 1).padStart(2, '0'));
                            const dayOfWeek = currentDateOfMonth.day();
                            const dayName = joursNoms[dayOfWeek];
                            let isHoliday = false;
                            let holidayName = '';
                            if (response.holidays) {
                                response.holidays.forEach(holiday => {
                                    const holidayStart = moment(holiday.start);
                                    const holidayEnd = moment(holiday.end);
                                    if (currentDateOfMonth.isBetween(holidayStart, holidayEnd, 'day', '[]')) {
                                        isHoliday = true;
                                        holidayName = holiday.name;
                                    }
                                });
                            }
                            const classes = ['day-name-cell'];
                            if (isHoliday) classes.push('holiday-header');
                            else if (dayOfWeek === 0) classes.push('weekend-header');
                            const displayText = isHoliday ? holidayName : dayName;
                            return `<th class="${classes.join(' ')}">${displayText}</th>`;
                        }).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${response.pointage.map((salarie, index) => `
                        <tr class="${index % 2 === 0 ? '' : 'alternate-row'}">
                            <td class="matricule">${salarie.n_matricule_entreprise || "N/A"}</td>
                            <td class="employee-name">${salarie.nom || "N/A"}</td>
                            <td class="fonction">${salarie.fonction || "N/A"}</td>
                          ${Array.from({ length: response.daysInMonth }, (_, day) => {
    const currentDateOfMonth = moment(selectedMonth + '-' + String(day + 1).padStart(2, '0'));
    const isWeekend = currentDateOfMonth.day() === 0;

    // ← Une seule déclaration de mark
    const mark = salarie.days[day + 1] || '';

    let isHoliday = false;
    if (response.holidays) {
        response.holidays.forEach(holiday => {
            const holidayStart = moment(holiday.start);
            const holidayEnd = moment(holiday.end);
            if (currentDateOfMonth.isBetween(holidayStart, holidayEnd, 'day', '[]')) {
                isHoliday = true;
            }
        });
    }

    const classes = ['day-cell'];
    if (isHoliday) classes.push('holiday');
    else if (isWeekend) classes.push('weekend');

    // ← Affichage selon la valeur
    let displayContent = '';
    if (mark === 'X') {
        displayContent = '<span class="present-mark">X</span>';
    } else if (mark === 'C') {
        displayContent = '<span style="color: #e67e22; font-weight: bold;">C</span>';
    }

    return `<td class="${classes.join(' ')}">${displayContent}</td>`;
}).join('')}
                            <td class="total-cell">${salarie.total || 0}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </body>
        </html>`;
}

        // Gestion du bouton d'impression
        const printPointageButton = document.getElementById("printPointage");
        if (printPointageButton) {
            printPointageButton.addEventListener("click", function () {
                console.log("📌 Clic sur imprimer");
                const selectedMonth = monthFilter.val();
                if (!selectedMonth) {
                    showToast("Veuillez sélectionner un mois", "warning");
                    return;
                }
                if (!window.pointageByMonthRoute) {
                    console.error("❌ Route pointageByMonthRoute non définie");
                    showToast("Erreur : Route non définie", "danger");
                    return;
                }

                const originalText = printPointageButton.innerHTML;
                printPointageButton.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i> Chargement...';
                printPointageButton.disabled = true;

                $.ajax({
                    url: window.pointageByMonthRoute,
                    method: "GET",
                    data: { month: selectedMonth },
                    timeout: 30000,
                    success: function (response) {
                        printPointageButton.innerHTML = originalText;
                        printPointageButton.disabled = false;

                        if (response.error) {
                            console.error("❌ Erreur:", response.error);
                            showToast("Erreur : " + response.error, "danger");
                            return;
                        }

                        if (!response.pointage || response.pointage.length === 0) {
                            console.warn("⚠️ Aucune donnée:", response);
                            showToast("Aucune donnée pour ce mois", "warning");
                            return;
                        }

                        const printDoc = printFrame.contentDocument || printFrame.contentWindow.document;
                        printDoc.open();
                        printDoc.write(generatePrintHTML(response, selectedMonth));
                        printDoc.close();
                        console.log('Document écrit dans l\'iframe');

                        printFrame.contentWindow.focus();
                        setTimeout(() => printFrame.contentWindow.print(), 500);
                    },
                    error: function (xhr, status, error) {
                        printPointageButton.innerHTML = originalText;
                        printPointageButton.disabled = false;
                        console.error("❌ Erreur AJAX:", { status, error, response: xhr.responseText });
                        showToast("Erreur lors de la récupération des données", "danger");
                    },
                });
            });
        } else {
            console.error("❌ Bouton printPointage non trouvé");
        }

        // Configuration FullCalendar
        if (calendarEl) {
            calendar = new FullCalendar.Calendar(calendarEl, {
                locale: "fr",
                initialView: currentView,
                height: "auto",
                events: fetchEvents,
                editable: false,
                dragScroll: true,
                dayMaxEvents: 2,
                moreLinkText: n => `+${n} autres`,
                eventResizableFromStart: false,
                nowIndicator: true,
                weekNumbers: false,
                aspectRatio: 1.8,
                customButtons: {
                    sidebarToggle: { text: "☰", hint: "Ouvrir le menu" },
                },
                headerToolbar: {
                    start: "sidebarToggle prev,next",
                    center: "title",
                    end: "dayGridMonth,timeGridWeek,timeGridDay,listMonth",
                },
                buttonText: {
                    today: "Aujourd'hui",
                    month: "Mois",
                    week: "Semaine",
                    day: "Jour",
                    list: "Agenda",
                },
                direction: direction,
                initialDate: new Date(),
                navLinks: true,
                selectable: true,
                selectMirror: true,
                eventClassNames: ({ event: calendarEvent }) => {
                    const calendarId = calendarEvent._def.extendedProps.calendar;
                    const isHoliday = calendarEvent._def.extendedProps.isHoliday || false;
                    const colorName = isHoliday ? 'success' : (calendarsColor[calendarId] || 'primary');
                    const classes = ['fc-event-modern', `fc-event-${colorName}`, 'shadow-sm', 'border-0'];
                    if (isHoliday) {
                        classes.push('fc-event-holiday');
                    }
                    return classes;
                },
                eventContent: function (arg) {
                    const event = arg.event;
                    const isHoliday = event.extendedProps.isHoliday || false;
                    const eventCalendar = event.extendedProps.calendar;
                    let projectId = eventCalendar && eventCalendar.startsWith('projet-')
                        ? eventCalendar.replace('projet-', '')
                        : eventCalendar === 'sans-projet' ? 'sans-projet' : '';
                    const date = moment(event.start).format('YYYY-MM-DD');

                    if (isHoliday) {
                        return {
                            html: `<div class="fc-event-content-modern holiday-event">
                                <span class="event-holiday-title"><i class="bx bx-calendar-event me-1"></i>${event.title}</span>
                            </div>`
                        };
                    }

                    let title = event.title.replace(
                        /<span class='present'>(\d+)<\/span>/g,
                        `<span class='present-counter badge bg-success rounded-pill ms-1' 
                            data-project-id='${projectId}' 
                            data-date='${date}' 
                            data-status='1' 
                            style='cursor: pointer; font-size: 0.7em;' 
                            title='${moment(arg.event.start).format('DD/MM/YYYY')} - Cliquer pour voir les présents'>
                            <i class='bx bx-check me-1'></i>$1
                        </span>`
                    ).replace(
                        /<span class='absent'>(\d+)<\/span>/g,
                        `<span class='absent-counter badge bg-danger rounded-pill ms-1' 
                            data-project-id='${projectId}' 
                            data-date='${date}' 
                            data-status='0' 
                            style='cursor: pointer; font-size: 0.7em;' 
                            title='${moment(arg.event.start).format('DD/MM/YYYY')} - Cliquer pour voir les absents'>
                            <i class='bx bx-x me-1'></i>$1
                        </span>`
                    );

                    return { html: `<div class="fc-event-content-modern">${title}</div>` };
                },
                eventDidMount: function (info) {
                    const presentElement = info.el.querySelector(".present-counter");
                    const absentElement = info.el.querySelector(".absent-counter");
                    [presentElement, absentElement].forEach(element => {
                        if (element) {
                            element.addEventListener("click", function (e) {
                                e.stopPropagation();
                                this.style.transform = "scale(0.9)";
                                setTimeout(() => this.style.transform = "scale(1)", 150);
                                showEmployeeList(
                                    this.getAttribute("data-project-id"),
                                    this.getAttribute("data-date"),
                                    this.getAttribute("data-status")
                                );
                            });
                            element.addEventListener("mouseenter", function () {
                                this.style.transform = "scale(1.1)";
                                this.style.transition = "transform 0.2s ease";
                            });
                            element.addEventListener("mouseleave", function () {
                                this.style.transform = "scale(1)";
                            });
                        }
                    });
                    info.el.style.cursor = "pointer";
                    info.el.style.transition = "all 0.2s ease";
                    info.el.addEventListener("mouseenter", function () {
                        this.style.transform = "translateY(-1px)";
                        this.style.boxShadow = "0 4px 12px rgba(0,0,0,0.15)";
                    });
                    info.el.addEventListener("mouseleave", function () {
                        this.style.transform = "translateY(0)";
                        this.style.boxShadow = "";
                    });
                },
                datesSet: function (info) {
                    setTimeout(modifyToggler, 100);
                    const view = calendar.view.type;
                    if (view !== currentView) {
                        currentView = view;
                        localStorage.setItem("calendarView", view);
                    }
                    const titleElement = document.querySelector(".fc-toolbar-title");
                    if (titleElement) {
                        titleElement.style.background = "linear-gradient(45deg, #007bff, #6f42c1)";
                        titleElement.style.webkitBackgroundClip = "text";
                        titleElement.style.webkitTextFillColor = "transparent";
                        titleElement.style.fontWeight = "700";
                    }
                    // Synchronize monthFilter with the calendar's current month
                    const currentMonth = moment(calendar.getDate()).format("YYYY-MM");
                    console.log("📅 Calendar active month:", currentMonth, "from calendar.getDate():", calendar.getDate());
                    if (!monthFilter.find(`option[value="${currentMonth}"]`).length) {
                        const currentMonthText = moment(calendar.getDate()).format("MMMM YYYY").charAt(0).toUpperCase() + moment(calendar.getDate()).format("MMMM YYYY").slice(1);
                        monthFilter.append(`<option value="${currentMonth}">${currentMonthText}</option>`);
                        monthFilter.select2({
                            placeholder: "Sélectionner un mois",
                            allowClear: false,
                            width: "100%",
                            dropdownCssClass: "select2-dropdown-modern",
                            selectionCssClass: "select2-selection-modern",
                            templateResult: function (option) {
                                if (!option.id) return option.text;
                                return $(`<span><i class="bx bx-calendar me-2"></i>${option.text}</span>`);
                            },
                            templateSelection: function (option) {
                                if (!option.id) return option.text;
                                return $(`<span><i class="bx bx-calendar me-1"></i>${option.text}</span>`);
                            },
                        });
                        console.log("📅 Ajout dynamique du mois:", currentMonth);
                    }
                    if (monthFilter.val() !== currentMonth) {
                        isProgrammaticChange = true;
                        monthFilter.val(currentMonth).trigger("change.select2");
                        console.log("📅 Synchronisation du monthFilter avec le calendrier:", currentMonth);
                        setTimeout(() => { isProgrammaticChange = false; }, 100);
                    }
                },
                viewDidMount: function () {
                    setTimeout(modifyToggler, 100);
                },
                eventSourceFailure: error => console.error("❌ Erreur chargement événements:", error),
                loading: isLoading => calendarEl.style.opacity = isLoading ? "0.6" : "1",
            });

            calendar.render();
            calendarEl.classList.add("calendar-loaded");
            modifyToggler();

            // Forcer la synchronisation initiale
            const initialMonth = moment().format("YYYY-MM");
            calendar.gotoDate(`${initialMonth}-01`);
            monthFilter.val(initialMonth).trigger("change.select2");
            console.log("📅 Forcer l'initialisation au mois courant:", initialMonth);
        }

        // Synchronisation avec inline calendar
        if (inlineCalInstance && calendar) {
            calendar.on("datesSet", function (info) {
                const currentDate = calendar.getDate();
                if (
                    inlineCalInstance.selectedDates.length === 0 ||
                    !moment(inlineCalInstance.selectedDates[0]).isSame(currentDate, "day")
                ) {
                    inlineCalInstance.setDate(currentDate, false);
                }
            });
        }

        // Gestion du redimensionnement
        function handleResize() {
            const width = window.innerWidth;
            let newView = currentView;
            if (width < 576) newView = "listMonth";
            else if (width < 768) newView = "timeGridWeek";
            else if (width >= 1200) newView = localStorage.getItem("calendarView") || "dayGridMonth";
            if (calendar && calendar.view.type !== newView) {
                calendar.changeView(newView);
            }
            if (calendar) {
                calendar.setOption("height", width < 768 ? "auto" : "auto");
            }
        }

        window.addEventListener("resize", debounce(handleResize, 250));

        // Raccourcis clavier
        document.addEventListener("keydown", e => {
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case "ArrowLeft":
                        e.preventDefault();
                        if (calendar) calendar.prev();
                        break;
                    case "ArrowRight":
                        e.preventDefault();
                        if (calendar) calendar.next();
                        break;
                    case "t":
                        e.preventDefault();
                        if (calendar) calendar.today();
                        break;
                }
            }
        });

        // Fonction debounce
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        console.log("🎉 Calendrier initialisé avec succès");
        console.log(`📊 ${currentEvents.length} événements disponibles`);
        console.log(`🎨 ${Object.keys(calendarsColor).length} calendriers configurés`);
    } catch (error) {
        console.error('Erreur lors de l\'initialisation:', error);
        showToast('Erreur d\'initialisation de l\'application', 'danger');
    }
});

// Gestion du clic sur le bouton toggle-status-btn
$(document).on('click', '.toggle-status-btn', function () {
    const btn = $(this);
    const salarieId = btn.data('salarie-id');
    const projectId = btn.data('project-id');
    const date = btn.data('date');
    const currentStatus = parseInt(btn.data('current-status'));
    const newStatus = currentStatus === 1 ? 0 : 1;

    // Désactiver le bouton pendant la requête
    btn.prop('disabled', true);
    const originalIcon = btn.find('i').attr('class');
    const originalText = btn.find('span').text();
    
    btn.find('i').removeClass().addClass('bx bx-loader-alt bx-spin text-primary');
    btn.find('span').text('Mise à jour...');

    const questionText = newStatus === 1
        ? "Confirmer que ce salarié est PRÉSENT ?"
        : "Confirmer que ce salarié est ABSENT ?";
    
    const confirmText = newStatus === 1 ? "Marquer présent" : "Marquer absent";

    Swal.fire({
        title: "Confirmation",
        text: questionText,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: "Annuler",
        preConfirm: () => {
            return $.ajax({
                url: window.togglePresenceRoute,
                method: 'POST',
                data: {
                    salarie_id: salarieId,
                    id_projet: projectId !== 'sans-projet' ? projectId : null,
                    date: date,
                    statuts: newStatus,
                    _token: window.csrfToken
                }
            });
        }
    }).then((result) => {
        if (result.isConfirmed) {
            if (result.value.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Succès',
                    text: result.value.message,
                    timer: 1500,
                    showConfirmButton: false
                });
                
                // Mettre à jour l'icône et le texte du bouton
                btn.data('current-status', newStatus);
                btn.find('i').removeClass().addClass(
                    newStatus === 1 ? 'bx bx-user-check text-success' : 'bx bx-user-x text-danger'
                );
                btn.find('span').text(newStatus === 1 ? 'Présent' : 'Absent');
                btn.prop('title', newStatus === 1 ? 'Marquer absent' : 'Marquer présent');
                
                // Recharger la liste et le calendrier
                showEmployeeList(projectId, date, newStatus.toString());
                if (window.calendar) {
                    window.calendar.refetchEvents();
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: result.value.message || 'Une erreur est survenue'
                });
                btn.find('i').attr('class', originalIcon);
                btn.find('span').text(originalText);
            }
        }
        btn.prop('disabled', false);
    }).catch(() => {
        btn.prop('disabled', false);
        btn.find('i').attr('class', originalIcon);
        btn.find('span').text(originalText);
    });
});

// Gestion des erreurs globales
window.addEventListener('error', function (event) {
    console.error('Erreur globale:', event.message, event.filename, event.lineno);
});