//////////////////////////////////////////////
/////////// DEZE BESTAND IS VOOR INDEX////////
//////////////////////////////////////////////

document.addEventListener("DOMContentLoaded", function() {
    // Animaties voor verschillende containers
    const bovenContainerLinks = document.querySelector('.boven-container-links');
    const percentageText = document.getElementById('percentageText');
    const h3Text = document.querySelector('.boven-container-links h3');

    bovenContainerLinks.addEventListener('animationend', () => {
        percentageText.classList.add('visible');
        h3Text.classList.add('visible');
    });

    const bovenContainerRechts = document.querySelector('.boven-container-rechts');
    const workText = document.getElementById('workText');

    bovenContainerRechts.addEventListener('animationend', () => {
        workText.classList.add('visible');
    });

    const onderContainer = document.querySelector('.onder-container');
    const welkomContainer = document.getElementById('welkomContainer');

    onderContainer.addEventListener('animationend', () => {
        welkomContainer.classList.add('visible');
    });

    // Pop-up functionaliteit
    const popupOverlay = document.getElementById("popup-overlay");
    const popup = document.getElementById("popup");

    document.querySelectorAll(".project-info-popup").forEach(button => {
        button.addEventListener("click", function() {
            let userId = this.getAttribute("data-user-id");

            console.log("Gebruiker ID: " + userId);  // Dit logt de user ID in de console.

            if (!userId) {
                alert("Geen gebruiker ID gevonden!");
                return;
            }

            // AJAX-aanroep naar PHP om alle gekoppelde projecten op te halen
            fetch(`gebruiker-project-info.php?user_id=${userId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Netwerkprobleem of serverprobleem');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("Projectdata ontvangen:", data);  // Log de volledige data
                    if (data.status === 'success') {
                        let projectsHtml = ''; // Lege string voor alle projecten
                        data.projects.forEach(project => {
                            console.log("Project naam:", project.project_naam);  // Log projectnaam voor elk project

                            projectsHtml += `
                                <div class='project-item'>
                                    <h5 class="project-title" onclick="toggleDetails(this)"><span style="font-size: smaller;">▶</span> ${project.project_naam}</h5>
                                    <div class="project-details">
                                        <p><strong>Klant:</strong> ${project.klant_voornaam} ${project.klant_achternaam}</p>
                                        <p><strong>Contracturen:</strong> ${project.contract_uren} uur</p>
                                        <p class="project-beschrijving"><strong>Beschrijving:</strong> ${project.beschrijving}</p>
                                    </div>
                                    <hr>
                                </div>
                            `;
                        });

                        document.getElementById("popup-content").innerHTML = projectsHtml;
                        popupOverlay.style.display = "flex"; // Overlay tonen
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error("Fout bij ophalen projectinfo:", error);
                    alert('Er is een fout opgetreden bij het ophalen van de projectinformatie.');
                });
        });
    });

    // Sluitknop voor de pop-up
    document.querySelector(".close").addEventListener("click", function() {
        popupOverlay.style.display = "none";
    });

    // Sluit pop-up als er buiten wordt geklikt
    popupOverlay.addEventListener("click", function(event) {
        if (event.target === popupOverlay) {
            popupOverlay.style.display = "none";
        }
    });
});

// Functie om de projectdetails te tonen of te verbergen
function toggleDetails(projectTitle) {
    const details = projectTitle.nextElementSibling; // Haalt het volgende sibling-element op, wat de projectdetails is

    if (details.style.display === "none" || details.style.display === "") {
        details.style.display = "block"; // Toon de details
    } else {
        details.style.display = "none"; // Verberg de details
    }
}
