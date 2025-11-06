# Asset Manager
These are facilities provided to the administrator users role 2 and 3 and are included as a sub-section in the Adminitrator Settings section.

This is not indended, at least initially, to be a fully fledged asset management system. Primarily we need a way to have a list that contains the information needed to produce the QR codes used by the application.

It should be possible to select different sticker sheet types that allow the QR codes to be printed with the asset number and brief name of the asset.

## Asset List Table
Fields are:
 * id
 * list_owner (participant id of the list owner or creator)
 * client_id (the participant id of the client that owns the asset)
 * asset_no (the asset number))
 * item (the asset or item name or type of item)
 * description (A general description of the item, if needed)
 * location (the site id where the item is located)
 * mngr (the manager id responsible for approving repairs and maintenance)
 * sp (service provider id)
 * star (a boolean indicating gif the item is to be added to a quick access list of assets)
 * status

Future fields may include purchase date, value, expected life, etc.


## Client Dashboard
The asset list for a client is aimed at making it easy for a client to manage asset QR codes and ultimately collect life-cycle maintenance data per asset. 
The client would append the QR code stickers to the assets at the client's locations.
The client can decide whether to include service provider identity in the QR code or not
Provide a way to upload a list of assets in csv format. These uploaded assets will be appended to those already uploaded. 
It must be possible to edit and delete assests, or to tag them as unavailable
It should be possible for an role 2 user to select some or all assets for printing of QR codes

## Service Provider Dashboard
The asset list for a service provider is aimed at allowing a service provider to manage QR codes for the assets of a client for which the service provider is responsible. 
The service provider would append the QR code stickers to the assets at the client's locations.
It should be possible for an role 3 user to select some or all assets of a particular client for printing of QR codes
The user should be able to select, for a client the list of assets to be managed, upload and edit as needed