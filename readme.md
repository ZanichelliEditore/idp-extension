# Zanichelli packages

## Come integrare la libreria
### Step 1 - Creazione chiave OAuth
Andare su bitbucket.org, cliccare sull'immagine del profilo in basso a sinistra e andare su Bitbucket settings. Successivamente cliccare sulla voce OAuth 
e aggiungere un consumer. Per aggiungere un comsumer � necessario specificare le seguenti voci:

  * Name (Nome a piacere)
  * Callback URL (URL fittizio esempio: http://www.example.com)
  * Nelle permissions selezionare nei Projects il permesso di sola lettura

Salvando il consumer si vanno a generare una Key e una Secret Key, che saranno richieste per il download della libreria.

### Step 2 - Aggiungere la dipendenza al composer.json
Per aggiungere la libreria � necessario effettuare le seguenti modifiche al file composer.json:

  * Aggiungere **"zanichelli/zanichelli-idp": "dev-master"** nei require
  * Aggiungere sotto autoload -> classmap la riga **"vendor/zanichelli"**

### Step 3 - Eseguire il composer
Entrare dentro un CMD, posizionarsi nella root del progetto in cui si trova il file composer.json ed eseguire il comando **composer update**. Durante 
l'esecuzione vengono richiesti i parametri che abbiamo generato nello Step 1.
**N.B.** A volte potrebbe essere richiesto un token; in questo caso cliccate invio e ignorate l'errore visualizzato.

## Settings Envirorment (IDP)
Nel file di envirorment **.env** � necessario aggiungere tre variabili obbligatorie che gestiscono il login con l'IDP attraverso il middleware IDP:

  * IDP_URL=https://idp.zanichelli.it/loginForm
  * IDP_TOKEN_URL=https://idp.zanichelli.it/v1/loginWithToken
  * IDP_LOGOUT_URL=https://idp.zanichelli.it/v1/logout

Per chi volesse gestire il login manualmente con una View custom della propria applicazione, senza passare dalla form dell'IDP, deve aggiungere 
anche la seguente variabile:

  * IDP_LOGIN_URL=https://idp.zanichelli.it/v1/login

## Implementazione del Middleware IDP
Nella cartella App\Http\Middleware aggiungere una classe che estende **IdpMiddleware** (namespace Zanichelli\IdentityProvider\Middleware) presente 
nella libreria. La classe dovr� implementare due metodi astratti:

  * **retrievePermissions**: questo metodo prende in ingresso l'id dell'utente e l'array dei ruoli che appartengono ad esso. 
    Nel corpo del metodo si dovranno recuperare i permessi dell'utente in base ai suoi ruoli. Il metodo, infine, deve ritornare un array 
    di permessi (array di stringhe).
  * **addExtraParametersToUser**: il metodo prende in ingresso un'istanza di un utente, in cui � possibile aggiungere campi 
    extra. Esempio, se il mio utete deve contenere il codice funzionario dovr� scrivere semplicemente $user->agentCode = 052.
    
## Modifica del AuthServiceProvider
Nella classe **AuthServiceProvider**, presente nella cartella App\Http\Middleware, aggiungere dentro il metodo boot il seguente codice:

    Auth::provider('z-provider', function ($app, array $config){
        return new ZAuthServiceProvider();
    });
    
    Auth::extend('z-session', function ($app, $name, array $config){
        return ZGuard::create($this->app['session.store'], Auth::createUserProvider($config['provider']));
    });
    
La prima funzione crea un nuovo driver la l'AuthServiceProvider con id **z-provider**, mentre la seconda funzione crea un driver per una nuova guardia con
id **z-session**. Il passo successivo � quello di modificare le configurazioni dell'applicazione in modo da utilizzare i driver creati.

## Modifica del file config/auth.php
Nel file di configurazione dobbiamo creare una nuova guardia e un nuovo provider che utilizzino i nuovi driver. Per semplificare le cose i nomi della
guardia e del provider sono gli stessi a quelli dati ai driver creati precedentemente.

Come prima cosa creaiamo una nuova guardia, aggiungendo all'array di guards il seguente valore:

    'z-session' => [
        'driver' => 'z-session',
        'provider => 'z-provider'
    ]
    
Una volta  creata questa guardia, la dobbiamo impostare come quella di default. Quindi, andiamo nell'array **defaults** e cambiamo il valore di **guard** in
**z-session**. Successivamente andiamo ad aggiungere all'array **providers** il seguente valore:

    'z-provider' => [
        'driver' => 'z-provider'
    ]
    
# Basics
Con queste modifiche � possibile utilizzare alcune funzionalit� di Laravel per la gestione degli utenti autenticati. La classe di utilit� di Laravel che 
ha il compito di gestire gli utenti � **Auth**. Questa classe permette di accedere ai seguenti metodi Facades:

  * **Auth::check()** ritorna true se c'� un utente loggato, altrimenti false
  * **Auth::guest()** ritorna true se NON c'� un utente loggato, altrimenti false
  * **Auth::user()** ritorna un'istanza di un utente (classe ZUser) se loggato, altrimenti null 
  * **Auth::id()** ritorna l'id dell'utente se loggato, altrimenti null
  * **Auth::setUser($user)** imposta l'utente loggato nella session
  * **Auth::attempt($credentials, $remember)** effettua il login con l'IDP senza passare per la form dell'IDP; ritorna true se andato a buon fine, altrimenti false
  * **Auth::logout()** effettua il logout dell'utente e lo cancella dalla sessione; ritorna true se andato a buon fine, altrimenti false
    











