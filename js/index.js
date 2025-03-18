document.addEventListener("DOMContentLoaded", function() {
    // Animaties voor verschillende containers
    const bovenContainerLinks = document.querySelector('.boven-container-links');
    const percentageText = document.getElementById('percentageText');
    const h3Text = document.querySelector('.boven-container-links h3');

    bovenContainerLinks.addEventListener('animationend', () => {
        percentageText.classList.add('visible');
        h3Text.classList.add('visible');
    });

    const workText1 = document.getElementById('workText1');

    bovenContainerLinks.addEventListener('animationend', () => {
        workText1.classList.add('visible');
    });

    const bovenContainerRechts = document.querySelector('.boven-container-rechts');
    const workText2 = document.getElementById('workText2');

    bovenContainerRechts.addEventListener('animationend', () => {
        workText2.classList.add('visible');
    });

    const onderContainer = document.querySelector('.onder-container');
    const welkomContainer = document.getElementById('welkomContainer');

    onderContainer.addEventListener('animationend', () => {
        welkomContainer.classList.add('visible');
    });

    // Pop-up functionaliteit
    const popupOverlay = document.getElementById("popup-overlay");
    const popupContent = document.getElementById("popup-content");

    // Knop voor het tonen van de projectinformatie
    document.querySelectorAll(".project-info-popup").forEach(button => {
        button.addEventListener("click", function() {
            let userId = this.getAttribute("data-user-id");

            if (!userId) {
                alert("Geen gebruiker ID gevonden!");
                return;
            }

            // AJAX-aanroep naar PHP om alle gekoppelde projecten op te halen
            fetch(`gebruiker-project-info.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        let projectsHtml = '<h6>Gekoppelde projecten</h6>';
                        data.projects.forEach(project => {
                            projectsHtml += `
                                <div class='project-item'>
                                    <h5 class="project-title" onclick="toggleDetails(this)">
                                        <span style="font-size: smaller;">▶</span> ${project.project_naam}
                                    </h5>
                                    <div class="project-details">
                                        <p><strong>Klant:</strong> ${project.klant_voornaam} ${project.klant_achternaam}</p>
                                        <p><strong>Contracturen:</strong> ${project.contract_uren} uur</p>
                                        <p class="project-beschrijving"><strong>Beschrijving:</strong> ${project.beschrijving}</p>
                                    </div>
                                    <hr>
                                </div>
                            `;
                        });

                        popupContent.innerHTML = projectsHtml;
                        popupOverlay.style.display = "flex";
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

    // Knop voor het tonen van de voortgangspercentage met projectnamen
    document.querySelector(".progress-info-popup").addEventListener("click", function() {
        let userId = this.getAttribute("data-user-id");

        if (!userId) {
            alert("Geen gebruiker ID gevonden!");
            return;
        }

        // AJAX-aanroep om projectvoortgang op te halen
        fetch(`project-progress-bar.php`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    let progressHtml = '<h6>Projecten Voortgang</h6>';
                    data.projects.forEach(project => {
                        progressHtml += `
                        <div class='project-progress-item'>
                            <h5 class="project-progress-title" onclick="toggleProgress(this)">
                                <span style="font-size: smaller;">▶</span> ${project.project_naam}
                            </h5>
                            <div class="progress-details">
                                <div>${project.remainingHours} uur resterend</div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-popup" style="width: ${project.progressPercentage};"></div>
                                </div>
                                <div>${project.progressPercentage}</div>
                            </div>
                            <hr>
                        </div>
                    `;
                    });

                    popupContent.innerHTML = progressHtml;
                    popupOverlay.style.display = "flex";
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error("Fout bij ophalen projectvoortgang:", error);
                alert('Er is een fout opgetreden bij het ophalen van de projectvoortgang.');
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
    const details = projectTitle.nextElementSibling;

    if (details.style.display === "none" || details.style.display === "") {
        details.style.display = "block";
    } else {
        details.style.display = "none";
    }
}

// Functie om de projectvoortgang te tonen of te verbergen
function toggleProgress(projectTitle) {
    const progressDetails = projectTitle.nextElementSibling;

    if (progressDetails.style.display === "none" || progressDetails.style.display === "") {
        progressDetails.style.display = "block";
    } else {
        progressDetails.style.display = "none";
    }
}
