document.addEventListener("DOMContentLoaded", function () {
    const categorySelect = document.getElementById("categorySelect");
    const fromSelect = document.getElementById("fromSelect");
    const toSelect = document.getElementById("toSelect");

    if (!categorySelect || !fromSelect || !toSelect) return;

    const units = {
        Length: ["Meters", "Kilometers", "Centimeters", "Millimeters", "Miles", "Feet", "Inches"],
        Temperature: ["Celsius", "Fahrenheit", "Kelvin"],
        Weight: ["Grams", "Kilograms", "Pounds", "Ounces"]
    };

    function populateDropdowns() {
        const cat = categorySelect.value;
        const availableUnits = units[cat] || [];

        const currentFrom = fromSelect.getAttribute("data-selected");
        const currentTo = toSelect.getAttribute("data-selected");

        fromSelect.innerHTML = "";
        toSelect.innerHTML = "";

        availableUnits.forEach(unit => {
            const opt1 = document.createElement("option");
            opt1.value = unit;
            opt1.textContent = unit;
            if (unit === currentFrom) opt1.selected = true;
            fromSelect.appendChild(opt1);

            const opt2 = document.createElement("option");
            opt2.value = unit;
            opt2.textContent = unit;
            if (unit === currentTo) opt2.selected = true;
            toSelect.appendChild(opt2);
        });

        // Clear attributes after initial setup so user choices override defaults
        fromSelect.removeAttribute("data-selected");
        toSelect.removeAttribute("data-selected");
    }

    categorySelect.addEventListener("change", populateDropdowns);
    populateDropdowns();
});