# Jeedom-BoschIndego
Script Jeedom pour les tondeuses robot Bosch Indego

## Remerciements
A 

## Installation
Créez un répertoire BoschIndego sous /var/www/html/plugins/script/core/ressources. Téléchargez et placez les fichiers indego.php, indego_doAction.php et indego_getState.php dans ce répertoire.

Il faut créer dans le même répertoire, le fichier indego-credentials.txt contenant sur la 1ère ligne l'username et sur la 2ème le password.

### Exécution dans Jeedom:

Créer un script qui sera exécuté chaque minute pendant la tonte:
La requête est: /var/www/html/plugins/script/core/ressources/BoschIndego/indego_getState.php
![Alt text](https://github.com/jpty/Jeedom-BoschIndego/blob/master/BoschIndegoScriptGetState.PNG)

Créer un deuxième script qui contiendra les commandes mow pause et returnToDock:
![Alt text](https://github.com/jpty/Jeedom-BoschIndego/blob/master/BoschIndegoScriptActions.PNG)


Ensuite dans un virtuel actualisé chaque minute, créer les commandes pour afficher les variables créées par le script.
La liste des variables disponibles: 
- BoschIndego_state,
