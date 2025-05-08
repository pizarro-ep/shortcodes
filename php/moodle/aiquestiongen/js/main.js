const textarea = document.querySelector(".aiqg-textarea");

textarea.addEventListener("input", () => {
    textarea.style.height = "auto";
    textarea.style.height = textarea.scrollHeight + "px";
});


const send = document.getElementById("send");
send.addEventListener("click", () => {
    const prompt = textarea.value;
    if (prompt) {
        textarea.value = "";
        textarea.style.height = "auto";
        textarea.style.height = textarea.scrollHeight + "px";
        fetch("/moodle/blocks/aiquestiongen/generate.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ prompt: prompt })
        })
            .then(response => response.json())
            .then(data => {
                console.log(data);
                const response = data.response;
                const output = document.getElementById("output");
                output.innerHTML = response;
            })
            .catch(error => {
                console.error("Ocurrió un error en la solicitud:", error);
            });
    }
});