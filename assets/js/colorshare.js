const span = document.getElementsByClassName("close")[0];
const modal = document.getElementById("shareCodeModal");
span.onclick = function () {
    modal.style.display = "none";
}
window.onclick = function (event) {
    if (event.target === modal) {
        modal.style.display = "none";
    }
}

document.getElementById("shareCodeSubmit").onclick = (
    function () {
        const url = 'api.php?code=' + document.getElementById("shareCode").value;
        fetch(url)
            .then(res => res.json())
            .then(data => importColors(data));
    });

function shareColors() {
    let colors = {};
    for (const input of colorInputs) {
        if (input.type === "color") {
            colors[input.id] = input.value;
        }
    }

    const url = 'api.php';
    fetch(url,
        {
            method: "POST",
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({colors: colors})
        }
    )
        .then(response => response.json())
        .then(data => {
            showShareModal(data);
        })
        .catch((error) => {
            console.error('Error:', error);
        });
}

function showShareModal(code) {
    document.getElementById("shareCodeTitle").textContent = "Give the following share code to your friends:";
    document.getElementById("shareCodeError").style.display = "none";
    document.getElementById("shareCodeSubmit").style.display = "none";
    document.getElementById("shareCode").value = code.code;
    modal.style.display = "block";
}

function showImportModal() {
    document.getElementById("shareCodeTitle").textContent = "Enter a share code:";
    document.getElementById("shareCodeError").style.display = "none";
    document.getElementById("shareCodeSubmit").style.display = "block";
    document.getElementById("shareCode").value = '';
    modal.style.display = "block";
}

function importColors(data) {
    if (data.hasOwnProperty('error')) {
        document.getElementById("shareCodeError").style.display = "block";
        document.getElementById("shareCodeError").textContent = "Unable to import colors: " + data.error;
    } else {
        const colors = data.colors;
        for (const input of colorInputs) {
            if (input.type === "color") {
                input.value = colors[input.id];
                updateColor(input)
            }
        }
        modal.style.display = "none";
    }
}