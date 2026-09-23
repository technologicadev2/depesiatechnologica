'use strict';

// Définir la Map et la palette globalement pour persistance
const salarieColors = new Map();
// Nouvelle palette de couleurs (douces et professionnelles)
const colorPalette = [
    '#3498DB', // Bleu vif
    '#2ECC71', // Vert émeraude
    '#E74C3C', // Rouge corail
    '#F1C40F', // Jaune moutarde
    '#9B59B6', // Violet
    '#1ABC9C', // Turquoise
    '#E67E22', // Orange
    '#34495E', // Bleu-gris foncé
    '#D35400', // Orange brûlé
    '#7F8C8D', // Gris élégant
    '#2980B9', // Bleu profond
    '#27AE60', // Vert forêt
    '#C0392B', // Rouge brique
    '#F39C12', // Jaune doré
    '#8E44AD'  // Violet foncé
];
let colorIndex = 0;

function getSalarieColor(salarieId, salarieName) {
    console.log('getSalarieColor:', { salarieId, salarieName });
    
    // Vérifier que salarieId est valide
    if (!salarieId) {
        console.warn('salarieId is null or undefined');
        return colorPalette[0]; // Couleur par défaut
    }
    
    // Si le salarié n'a pas encore de couleur, lui en assigner une
    if (!salarieColors.has(salarieId)) {
        salarieColors.set(salarieId, {
            color: colorPalette[colorIndex % colorPalette.length],
            name: salarieName || 'Employé inconnu'
        });
        colorIndex++;
        console.log('New color assigned to salarieId', salarieId, ':', salarieColors.get(salarieId));
    }
    
    return salarieColors.get(salarieId).color;
}

$(document).ready(function() {
    // Initialize DataTable
    $('#home-tab').on('shown.bs.tab', function(e) {
        if (!$.fn.DataTable.isDataTable('#congesTable')) {
            try {
                $('#congesTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/fr-FR.json'
                    },
                    pageLength: 10,
                    responsive: true,
                    destroy: true,
                    columnDefs: [
                        { targets: 8, orderable: false }
                    ],
                    dom: 'Bfrtip',
                    buttons: [
                        {
                            text: 'Demander un congé',
                            className: 'btn btn-primary',
                            action: function(e, dt, node, config) {
                                $('#modalConge').modal('show');
                            }
                        },
                        {
                            extend: 'excel',
                            className: 'btn btn-success',
                            text: 'Exporter en Excel'
                        },
                        {
                            extend: 'pdf',
                            className: 'btn btn-danger',
                            text: 'Exporter en PDF'
                        },
                        {
                            extend: 'csv',
                            className: 'btn btn-info',
                            text: 'Exporter en CSV'
                        }
                    ],
                    rowCallback: function(row, data, index) {
                        // Récupérer l'ID du salarié depuis l'attribut data de la ligne
                        const salarieId = $(row).data('salarie-id');
                        const nom = $(row).find('td:eq(0)').text().trim();
                        const prenom = $(row).find('td:eq(1)').text().trim();
                        const salarieName = `${nom} ${prenom}`.trim();
                        
                        if (salarieId) {
                            const color = getSalarieColor(salarieId, salarieName);
                            // Ajouter l'indicateur de couleur si pas déjà présent
                            const firstCell = $('td:eq(0)', row);
                            if (!firstCell.find('.employee-color-indicator').length) {
                                firstCell.prepend(
                                    `<span class="employee-color-indicator" style="background-color: ${color}; width: 12px; height: 12px; border-radius: 50%; display: inline-block; margin-right: 8px; border: 1px solid #ddd;"></span>`
                                );
                            }
                        } else {
                            console.warn('salarieId missing for row:', { row: row, data: data });
                        }
                    }
                });
            } catch (error) {
                console.error('Erreur DataTable:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Erreur lors de l\'initialisation du tableau: ' + error.message,
                    timer: 5000
                });
            }
        }
    });

    if ($('#home-tab').hasClass('active')) {
        $('#home-tab').trigger('shown.bs.tab');
    }

    // Initialize Calendar
    $('#profile-tab').on('shown.bs.tab', function(e) {
        if (!window.calendarInitialized) {
            initializeCalendar();
            window.calendarInitialized = true;
        } else {
            const calendarEl = document.getElementById('calendar');
            if (calendarEl && window.calendar) {
                window.calendar.refetchEvents();
            }
        }
    });

    if ($('#profile-tab').hasClass('active')) {
        $('#profile-tab').trigger('shown.bs.tab');
    }
});

function initializeCalendar() {
    const direction = document.documentElement.classList.contains('rtl') ? 'rtl' : 'ltr';
    const calendarEl = document.getElementById('calendar');
    const appCalendarSidebar = document.querySelector('.app-calendar-sidebar');
    const addEventSidebar = document.getElementById('addEventSidebar');
    const appOverlay = document.querySelector('.app-overlay');
    const leaveDetails = document.querySelector('#leaveDetails');
    const leaveEmployee = document.querySelector('#leaveEmployee');
    const leaveReason = document.querySelector('#leaveReason');
    const leavePeriod = document.querySelector('#leavePeriod');
    const inlineCalendar = document.querySelector('.inline-calendar');

    let inlineCalInstance;

    function createColorLegend() {
        let legendContainer = document.getElementById('color-legend');
        if (!legendContainer) {
            legendContainer = document.createElement('div');
            legendContainer.id = 'color-legend';
            legendContainer.className = 'mt-4 p-3 border rounded bg-light';
            const sidebarContent = document.querySelector('.app-calendar-sidebar .p-4');
            if (sidebarContent) {
                sidebarContent.appendChild(legendContainer);
            }
        }

        legendContainer.innerHTML = '<h6 class="mb-3 text-primary"><i class="bx bx-palette me-2"></i>Légende des couleurs</h6>';

        if (salarieColors.size > 0) {
            // Trier les salariés par nom pour un affichage cohérent
            const sortedSalaries = Array.from(salarieColors.entries()).sort((a, b) => 
                a[1].name.localeCompare(b[1].name)
            );
            
            sortedSalaries.forEach(([salarieId, data]) => {
                const legendItem = document.createElement('div');
                legendItem.className = 'legend-item d-flex align-items-center mb-2';
                legendItem.innerHTML = `
                    <div class="color-box me-2" style="width: 18px; height: 18px; background-color: ${data.color}; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></div>
                    <span class="legend-text small">${data.name}</span>
                `;
                legendContainer.appendChild(legendItem);
            });
        } else {
            legendContainer.innerHTML += '<div class="text-muted small">Aucun salarié à afficher</div>';
        }
    }

    const bsAddEventSidebar = new bootstrap.Offcanvas(addEventSidebar);

    if (inlineCalendar) {
        inlineCalInstance = inlineCalendar.flatpickr({
            monthSelectorType: 'static',
            inline: true,
            locale: {
                locale: 'fr',
                weekdays: {
                    shorthand: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
                    longhand: ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']
                },
                months: {
                    shorthand: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'],
                    longhand: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre']
                },
                firstDayOfWeek: 1,
                ordinal: function(nth) {
                    if (nth > 1) return 'ème';
                    return 'er';
                },
                weekAbbreviation: 'Sem',
                rangeSeparator: ' au ',
                scrollTitle: 'Défiler pour augmenter',
                toggleTitle: 'Cliquer pour basculer'
            }
        });
    }

    function modifyToggler() {
        const fcSidebarToggleButton = document.querySelector('.fc-sidebarToggle-button');
        if (fcSidebarToggleButton) {
            fcSidebarToggleButton.classList.remove('fc-button-primary');
            fcSidebarToggleButton.classList.add('d-lg-none', 'd-inline-block', 'ps-0');
            while (fcSidebarToggleButton.firstChild) {
                fcSidebarToggleButton.firstChild.remove();
            }
            fcSidebarToggleButton.setAttribute('data-bs-toggle', 'sidebar');
            fcSidebarToggleButton.setAttribute('data-overlay', '');
            fcSidebarToggleButton.setAttribute('data-target', '#app-calendar-sidebar');
            fcSidebarToggleButton.insertAdjacentHTML('beforeend', '<i class="bx bx-menu bx-sm text-body"></i>');
        }
    }

 function fetchEvents(info, successCallback, failureCallback) {
    console.log('Fetching events:', info.startStr, 'to', info.endStr);
    $.ajax({
        url: '/conges-administratif/calendar',
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            console.log('Raw response:', response);
            if (Array.isArray(response)) {
                console.log('Processing', response.length, 'events');
                
                const events = response.map((conge, index) => {
                    console.log(`Processing conge ${index}:`, conge);
                    
                    // Stocker la couleur dans la Map pour la légende
                    if (conge.extendedProps.salarieId && !salarieColors.has(conge.extendedProps.salarieId)) {
                        salarieColors.set(conge.extendedProps.salarieId, {
                            color: conge.backgroundColor,
                            name: conge.extendedProps.salarieName || 'Employé inconnu'
                        });
                    }
                    
                    return {
                        id: conge.id,
                        title: conge.title,
                        start: conge.start,
                        end: conge.end,
                        backgroundColor: conge.backgroundColor,
                        borderColor: conge.borderColor,
                        textColor: conge.textColor,
                        extendedProps: conge.extendedProps,
                        classNames: conge.classNames
                    };
                });

                console.log('Final events array:', events);
                console.log('Total events created:', events.length);
                
                // Créer la légende après avoir traité tous les événements
                setTimeout(createColorLegend, 100);
                successCallback(events);
            } else {
                console.error('Response not an array:', response);
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur',
                    text: 'Les données du calendrier ne sont pas au format attendu.',
                    timer: 5000
                });
                successCallback([]);
            }
        },
        error: function(xhr) {
            console.error('Error fetching events:', xhr.status, xhr.statusText);
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Erreur lors du chargement des données: ' + xhr.statusText,
                timer: 5000
            });
            failureCallback(xhr);
        }
    });
}

    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        events: fetchEvents,
        editable: false,
        dragScroll: true,
        dayMaxEvents: 3,
        eventResizableFromStart: false,
        height: 'auto',
        contentHeight: 650,
        aspectRatio: 1.5,
        eventDisplay: 'block',
        displayEventTime: false,
        moreLinkText: num => `+${num} autres`,
        customButtons: {
            sidebarToggle: { text: 'Barre latérale' },
            prev: {
                text: 'Précédent',
                click: () => calendar.prev()
            },
            next: {
                text: 'Suivant',
                click: () => calendar.next()
            }
        },
        headerToolbar: {
            start: 'sidebarToggle,prev,next,title',
            end: 'dayGridMonth,timeGridWeek,listWeek'
        },
        buttonText: {
            month: 'Mois',
            week: 'Semaine',
            list: 'Liste'
        },
        direction: direction,
        initialDate: new Date(),
        navLinks: true,
        eventClassNames: ({ event }) => ['fc-event-custom', event.extendedProps.statusClass || ''],
        eventContent: ({ event }) => ({
            html: `
                <div class="fc-event-content-custom" style="padding: 2px 4px; font-size: 11px; line-height: 1.2;">
                    <div class="fc-event-title" style="font-weight: 600;">${event.title}</div>
                    <div class="fc-event-details" style="font-size: 10px; opacity: 0.9;">
                        ${event.extendedProps.nombreJours} jour${event.extendedProps.nombreJours > 1 ? 's' : ''}
                    </div>
                </div>
            `
        }),
        eventDidMount: function(info) {
            const element = info.el;
            const event = info.event;
            const props = event.extendedProps;

            element.style.borderRadius = '6px';
            element.style.border = `2px solid ${event.borderColor}`;
            element.style.boxShadow = '0 2px 4px rgba(0,0,0,0.15)';
            element.style.cursor = 'pointer';
            element.style.transition = 'all 0.2s ease';

            element.addEventListener('mouseenter', () => {
                element.style.transform = 'scale(1.02)';
                element.style.boxShadow = '0 4px 8px rgba(0,0,0,0.2)';
                element.style.zIndex = '10';
            });

            element.addEventListener('mouseleave', () => {
                element.style.transform = 'scale(1)';
                element.style.boxShadow = '0 2px 4px rgba(0,0,0,0.15)';
                element.style.zIndex = 'auto';
            });

            const statusText = {
                0: 'En attente',
                1: 'Refusé',
                2: 'Approuvé'
            }[parseInt(props.approbation)] || 'Statut inconnu';

            element.title = `${props.salarieName}\nRaison: ${props.raison}\nDurée: ${props.nombreJours} jour${props.nombreJours > 1 ? 's' : ''}\nStatut: ${statusText}`;

            if (parseInt(props.approbation) === 1) {
                element.style.textDecoration = 'line-through';
            }
        },
        eventClick: function(info) {
            const event = info.event;
            const props = event.extendedProps;

            leaveEmployee.textContent = props.salarieName || 'N/A';
            leaveReason.textContent = props.raison || 'N/A';

            const startDate = moment(event.start);
            const endDate = event.end ? moment(event.end).subtract(1, 'day') : startDate;
            const periodText = startDate.isSame(endDate, 'day') ?
                startDate.format('DD/MM/YYYY') :
                `${startDate.format('DD/MM/YYYY')} - ${endDate.format('DD/MM/YYYY')}`;
            leavePeriod.textContent = `${periodText} (${props.nombreJours} jour${props.nombreJours > 1 ? 's' : ''})`;

            let statusElement = document.getElementById('leaveStatus');
            if (!statusElement) {
                const statusContainer = document.createElement('p');
                statusContainer.innerHTML = '<strong>Statut:</strong> <span id="leaveStatus"></span>';
                leaveDetails.appendChild(statusContainer);
                statusElement = document.getElementById('leaveStatus');
            }

            const statusConfig = {
                0: { text: '⏳ En attente', class: 'badge bg-warning' },
                1: { text: '❌ Refusé', class: 'badge bg-danger' },
                2: { text: '✅ Approuvé', class: 'badge bg-success' }
            }[parseInt(props.approbation)] || { text: 'Statut inconnu', class: 'badge bg-secondary' };

            statusElement.textContent = statusConfig.text;
            statusElement.className = statusConfig.class;

            bsAddEventSidebar.show();
        },
        datesSet: modifyToggler,
        viewDidMount: modifyToggler,
        dayCellDidMount: function(info) {
            const today = new Date();
            const cellDate = info.date;
            if (cellDate.toDateString() === today.toDateString()) {
                info.el.style.backgroundColor = '#f8f9ff';
                info.el.style.border = '2px solid #007bff';
            }
            if (cellDate.getDay() === 0 || cellDate.getDay() === 6) {
                info.el.style.backgroundColor = '#f8f8f8';
            }
        }
    });

    calendar.render();
    window.calendar = calendar;
    modifyToggler();

    if (inlineCalInstance) {
        inlineCalInstance.config.onChange.push(function(date) {
            calendar.changeView(calendar.view.type, moment(date[0]).format('YYYY-MM-DD'));
            modifyToggler();
            appCalendarSidebar.classList.remove('show');
            appOverlay.classList.remove('show');
        });
    }
}