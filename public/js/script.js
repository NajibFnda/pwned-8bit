// public/js/script.js
function checkEmail() {
    const email = document.getElementById('emailInput').value;
    const resultSec = document.getElementById('resultSection');
    
    if(email === "") return alert("Isi email dulu!");

    resultSec.style.display = "block";
    document.getElementById('displayEmail').innerText = email;

    // Logika simulasi: jika ada kata 'test' maka pwned, jika tidak maka safe
    if(email.includes("test")) {
        showPwned();
    } else {
        showSafe();
    }
    
    // Scroll otomatis ke bawah
    resultSec.scrollIntoView({ behavior: 'smooth' });
}

function showPwned() {
    document.getElementById('statusText').innerText = "PWNED";
    document.getElementById('statusCard').style.borderColor = "red";
    // Update isi tabel dengan data canva.com seperti di gambar
}