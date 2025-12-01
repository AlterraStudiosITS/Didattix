document.querySelector("form").addEventListener("submit", function(e){
    const emailInput = document.querySelector("input[name='email']");
    const email = emailInput.value.trim();

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if(!emailRegex.test(email)){
        e.preventDefault();
        alert("Inserisci un indirizzo email valido.");
        return;
    }

    if(email.length > 254){
        e.preventDefault();
        alert("L'email è troppo lunga.");
    }
});