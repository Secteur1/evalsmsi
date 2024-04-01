document.addEventListener('DOMContentLoaded', function() {
    // Écouteur d'événement pour détecter les changements dans la liste déroulante
    document.getElementById('referentiel_id').addEventListener('change', function() {
        // Récupération de l'option sélectionnée dans la liste déroulante
        var selectedOption = this.options[this.selectedIndex];

        // Récupération des données attributaires de l'option sélectionnée
        var filename = selectedOption.getAttribute('data-filename');
        var title = selectedOption.getAttribute('data-title');
        var nom = selectedOption.getAttribute('data-nom');

        // Préremplir les champs avec les valeurs récupérées
        document.getElementById('filename').value = filename;
        document.getElementById('title').value = title;
        document.getElementById('nom').value = nom;
    });
});