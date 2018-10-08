# Zanichelli packages

## Come integrare la libreria
### Step 1 - Creazione chiave OAuth
Andare su bitbucket.org, cliccare sull'immagine del profilo in basso a sinistra e andare su Bitbucket settings. Successivamente cliccare sulla voce OAuth 
e aggiungere un consumer. Per aggiungere un comsumer è necessario specificare le seguenti voci:
  * Name (Nome a piacere)
  * Callback URL (URL fittizio esempio: www.example.com)
  * Nelle permissions selezionare nei Projects il permesso di sola lettura

Salvando il consumer si vanno a generare una Key e una Secret Key, che saranno richieste per il download della libreria.

### Step 2 - Aggiungere la dipendenza al composer.json
Per aggiungere la libreria è necessario effettuare le seguenti modifiche al file composer.json:
  * Aggiungere **"zanichelli/zanichelli-idp": "dev-master"** nei require
  * Aggiungere sotto autoload -> classmap la riga **"vendor/zanichelli"**

### Step 3 - Eseguire il composer
Entrare dentro un CMD, posizionarsi nella root del progetto in cui si trova il file composer.json ed eseguire il comando **composer update**
