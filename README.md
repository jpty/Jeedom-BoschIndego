# Jeedom-BoschIndego
Script Jeedom pour les tondeuses robot Bosch Indego

## Remerciements
A 

## Installation
Créer un répertoire BoschIndego sous /var/www/html/plugins/script/core/ressources. Placez le fichier indego.php dans ce répertoire.

Il faut créer dans le même répertoire, le fichier indego-credentials.txt contenant sur la 1ère ligne l'username et sur la 2ème le password.

### Exécution dans Jeedom:

Créer un script qui sera exécuté chaque minute:
La requête est: /var/www/html/plugins/script/core/ressources/BoschIndego/indego_getState.php


Ensuite dans un virtuel actualisé chaque minute, créer les commandes pour afficher les variables créées par le script.
La liste des variables disponibles: 
- BoschIndego_state,
