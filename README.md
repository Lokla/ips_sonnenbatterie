# IP Symcon Module - Read values from Sonnenbattery
Module for IP Symcon which provides a connection to the battery of Sonnen.

Please note that I am in no relation to the company of Sonnen and that I created this module for my personal use.
I provide this for free use but do not guarantee functionality or anything. The module uses the API version 2.

The module has been tested with the 10kWh Sonnen Performance including Sonnen Protect module.

## Creation and configuration

Search for the device "Sonnenbatterie" in IP Symcon and create an instance. The configuration site requires some information which can be retrieved from the local Sonnen dashboard. You will have to connect to the website of the Sonnenbatterie to open this dashboard (http://{ip-of-your-battery}). You need to enter the API key and the local IP of the battery into the IP Symcon configuration.

The API key can be found in the Software-Integration section of the dashboard.

Optionally the module can retrieve the current status of the battery and compute daily values.
The daily values will not match the values of the sonnen cloud based web site, as they will be estimated by evaluating the retrieved data. My experience shows, that these values match quite well.


# IP Symcon Module - Auslesen der Sonnenbatterie
Dieses Modul für IP Symcon liest die aktuellen Daten der Sonnenbatterie aus und legt sie lokal als variablen in IP Symcon ab.

Ich stehe in keiner Verbindung zur Firma Sonnen. Dieses Modul wurde von mir zu meiner eigenen Verwendung erzeugt.
Gerne stelle ich es der Allgemeinheit zur Verwendung zur Verfügung, übernehme aber keinerlei Funktionsgarantie.

Das Modul benutzt die API Version 2 der Sonnenbatterie.

Das Module wurde mit einer 10kWh Sonnen Performance mit Sonnen Protect getestet.

## Anlegen und Konfiguration

In IPS nach dem Gerät "Sonnenbatterie" suchen und eine Instanz anlegen. Die Konfigurationsseite erfordert einige Angaben aus dem Sonnen Dashboard.
Zum Öffnen des lokalen Dashboards muss man zuerst der lokale Internetseit der Sonnenbatterie öffnen (http://{ip-of-your-battery}). Zur Verwendung benötigt man den API key und die IP der Sonnenbatterie.

Der APi Key ist im Dashboard Unter dem Punkt Software-Integration zu finden.

Optional kann neben den aktuellen Daten der Sonnenbatterie noch der Status ausgelesen und die Tagessummen berechnet werden.
Die Tagessummen werden zum Ende des Tages sicherlich nicht allzu genau sein, da diese anhand vom Interval aus den jeweils gelesenen Werten berechnet werden. Meine Erfahrung hat allerdings gezeigt, dass sie relativ gut mit den Werten aus der Batterie übereinstimmen.

