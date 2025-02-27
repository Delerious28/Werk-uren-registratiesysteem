<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>uren registreren</title>
    <link rel="stylesheet" href="css/uren-registreren.css">
</head>
<body>

<div class="bigbox">
    <div class="topheader">
        <div class="datum">
            <h3 id="date-today">21 feburari</h3>
        </div>
        <div class="week-nav">
            <button id="prev">&larr;</button>
            <button id="next">&rarr;</button>
        </div>
    </div>

    <div class="wrapper">
        <div class="blok-1">
            <label>Klant:</label>
            <input list="klantenn" id="klantField">
            <datalist id="klantenn">
                <option value="Klant A">
                <option value="Klant B">
            </datalist>

            <label>project naam</label>
            <input list="projs" id="prjt">
            <datalist id="projs">
                <option value="Project X">
                <option value="Project Y">
            </datalist>

            <label for="besch">Beschrijving:</label>
            <input type="text" id="besch">

            <label>starttijd:</label>
            <input type="time" id="begin">

            <label>eindtijd:</label>
            <input type="time" id="eind">

            <label>uren totaal</label>
            <input type="text" id="totaaluren" readonly>

            <button id="toevoegknop">+ Voeg toe</button>
        </div>
        <div class="block-b">
            <div class="overzicht">
                <div class="dag">
                    <span>Ma 26-02</span> |
                    <div class="info">
                        klant: <br> project: <br> beschrijving:
                    </div>
                    <div class="tijd">
                        8 uur <br> 08:00 - 17:00
                    </div>
                </div>
                <div class="dag">
                    <span>Di 27-02</span> |
                    <div class="info">
                        klant: <br> project: <br> beschrijving:
                    </div>
                    <div class="tijd">
                        7 uur <br> 09:00 - 16:00
                    </div>
                </div>
                <div class="dag">
                    <span>Wo 28-02</span> |
                    <div class="info">
                        klant: <br> project: <br> beschrijving:
                    </div>
                    <div class="tijd">
                        6 uur <br> 10:00 - 16:00
                    </div>
                </div>
                <div class="dag">
                    <span>Do 29-02</span> |
                    <div class="info">
                        klant: <br> project: <br> beschrijving:
                    </div>
                    <div class="tijd">
                        8 uur <br> 08:00 - 17:00
                    </div>
                </div>
                <div class="dag">
                    <span>Vr 01-03</span> |
                    <div class="info">
                        klant: <br> project: <br> beschrijving:
                    </div>
                    <div class="tijd">
                        7 uur <br> 09:00 - 16:00
                    </div>
                </div>

                <div class="totaalweek">
                    <span> totaal week: 36 uur</span>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
