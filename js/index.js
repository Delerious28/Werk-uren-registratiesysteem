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
    document.querySelectorAll(".project-info-popup").forEach(button => {
        button.addEventListener("click", function() {
            let projectId = this.getAttribute("data-project-id");

            if (!projectId) {
                alert("Geen project ID gevonden!");
                return;
            }

            // AJAX-aanroep naar PHP
            fetch(`gebruiker-project-info.php?project_id=${projectId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        document.getElementById("popup-title").textContent = data.project_naam;
                        document.getElementById("popup-description").textContent = `Beschrijving: ${data.beschrijving}`;
                        document.getElementById("popup-contract-uren").textContent = `Contracturen: ${data.contract_uren} uur`;
                        document.getElementById("popup-klant-naam").textContent = `Klant: ${data.klant_voornaam} ${data.klant_achternaam}`;

                        document.getElementById("popup").style.display = "block";
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error("Fout bij ophalen projectinfo:", error));
        });
    });

    // Sluitknop voor de pop-up
    document.querySelector(".close").addEventListener("click", function() {
        document.getElementById("popup").style.display = "none";
    });

    // Sluit pop-up als er buiten wordt geklikt
    window.addEventListener('click', function(event) {
        const popup = document.getElementById("popup");
        if (event.target === popup) {
            popup.style.display = "none";
        }
    });

});
