# 🔥 TOGO MARKET - RÉFÉRENCE PROJET & ARCHITECTURE BACKEND

Ce fichier est le **cœur du projet**. Il liste toutes les règles métiers, les logiques transactionnelles, et la structure détaillée de la base de données. Il servira de bible pour le développement backend (Laravel) et frontend (Flutter). Il a été calibré avec la plus haute précision par rapport au design UI et aux modèles natifs prévus (`models.dart`).

---

## 📌 1. RÈGLES MÉTIERS ET FLUX (BUSINESS LOGIC)

### 🛒 A. Règle du Panier / Commande
- **Un vendeur = Une commande (Pas de panier multi-vendeurs fusionné).** 
- Si le client veut acheter chez 2 vendeurs différents, il passe 2 commandes distinctes. Cela simplifie drastiquement le backend et la logistique.

### 💳 B. Paiements
- **Pas de TPE automatisés / pas de Stripe ou agrégateurs v1.**
- Le paiement se base sur la confiance et l'entente dans la table `conversations` (Chat) :
    1. **Paiement à la livraison** (Cash).
    2. **Transfert mobile (Flooz / TMoney)** où le client envoie une **Capture d'écran** de la transaction directement dans le chat validant sa bonne foi.

### 🚚 C. Livraison
- Gérée sur entente entre les deux parties : **Retrait en boutique** ou **Livraison arrangée par le vendeur**.

### 🛍️ D. Cycle de Vie d'un Produit (Les 3 Statuts)
C'est un point névralgique du projet (Ex: modèle Vinted).
Le produit passe par 3 états stricts :
1. **`actif`** : Le produit est visible par tout le monde dans le catalogue.
2. **`reserve`** : (Étape 1) Le client a passé la commande. Le produit disparaît du catalogue ou affiche "Déjà réservé".
3. **`vendu`** : (Étape 2) Le vendeur confirme la commande / réception du Cash ou TMoney.
*👉 CAS PARTICULIER : Si le vendeur annule/refuse, le produit repasse à l'état **`actif`** et redevient visible.*

### 🎨 E. Variations & État du Produit
- Le produit peut être **`Neuf`** ou d'**`Occasion`** (Très important pour les filtres de recherche et affiché dans l'UI).
- Pas de comptabilité de stock granulaire. Le produit possède des variations textuelles JSON (ex: Tailles: M,L / Couleurs: Bleu,Noir), et le client indique son choix lors de la commande.

### 💬 F. Conversations liées aux Produits
- Les chats sont initiés **depuis un produit spécifique**. Le vendeur voit immédiatement sur quel produit le client interagit. C'est parfait pour la négociation de prix et l'achat compulsif.

---

## 🗄️ 2. STRUCTURE DE LA BASE DE DONNÉES (SCHÉMA DÉTAILLÉ)

*Les noms de tables sont définis en anglais / pluriel (convention Laravel), mais les champs (colonnes) sont explicites et basés sur 100% de l'UI.*

### 1️⃣ Table `users` (Utilisateurs / Clients / Vendeurs)
**Rôle :** Gère l'authentification et les comptes de la plateforme.
* **`id`** (BIGINT, Primary Key)
* **`nom`** (VARCHAR, Nullable) : Nom complet (Optionnel à la première étape d'inscription).
* **`email`** (VARCHAR, Unique, Nullable) : Pour auth ou récupération de compte.
* **`telephone`** (VARCHAR, Unique) : Numéro avec préfixe international (+228) et contrôle strict des opérateurs togolais (Togocel ex: 90, 70 / Moov ex: 99, 96). C'est l'identifiant principal de connexion.
* **`mot_de_passe`** (VARCHAR, Nullable) : Hash de sécurité (Optionnel pour l'Auth Sociale).
* **`provider_name`** (VARCHAR, Nullable) : Fournisseur d'authentification sociale (`google`, `facebook`, `apple`).
* **`provider_id`** (VARCHAR, Nullable) : ID unique fourni par le réseau social.
* **`avatar_url`** (VARCHAR, Nullable) : Image de profil de l'utilisateur.
* **`role`** (ENUM: `client`, `vendeur`, `admin`) : Niveau de droit. Un `vendeur` a accès à l'espace vendeur.
* **`actif`** (BOOLEAN) : Permet de bannir un compte frauduleux.
* **`created_at`, `updated_at`**.

### 2️⃣ Table `boutiques` (`Seller` dans l'UI)
**Rôle :** Entité commerciale d'un vendeur.
* **`id`** (BIGINT, Primary Key)
* **`user_id`** (BIGINT, Foreign Key $\rightarrow$ `users.id`) : Le propriétaire.
* **`nom`** (VARCHAR) : Le nom commercial (ex: `shopName` - "Kofi Tech Shop").
* **`slug`** (VARCHAR, Unique) : Identifiant URL amical.
* **`description`** (TEXT, Nullable).
* **`logo_url`** (VARCHAR, Nullable) : L'avatar de la boutique affiché dans l'UI (`avatar`).
* **`(Champs de localisation supprimés)`** : L'adresse de la boutique et ses coordonnées GPS sont désormais gérées par la table polymorphe **`adresses`**.
* **`note_moyenne`** (DECIMAL, Nullable) : Note du vendeur (ex: `rating` - 4.8).
* **`temps_reponse`** (VARCHAR, Nullable) : Maintient le tracking côté front-end (ex: `responseTime` - "~10 min").
* **`statut`** (ENUM: `en_attente`, `approuve`, `suspendu`).
* **`created_at`, `updated_at`**.

### 3️⃣ Table `categories` (`Category` dans l'UI)
**Rôle :** Classification du catalogue.
* **`id`** (BIGINT, Primary Key)
* **`parent_id`** (BIGINT, Nullable, Foreign Key $\rightarrow$ `categories.id`).
* **`nom`** (VARCHAR) : Label complet.
* **`slug`** (VARCHAR, Unique).
* **`emoji`** (VARCHAR, Nullable) : Icone visuelle utilisée sur l'accueil (`emoji` - 🛍️, 👗, 📱).

### 4️⃣ Table `produits` (`Product` dans l'UI)
**Rôle :** La marchandise vitrine. A été scrupuleusement cartographiée par rapport aux maquettes.
* **`id`** (BIGINT, Primary Key)
* **`boutique_id`** (BIGINT, Foreign Key $\rightarrow$ `boutiques.id`) : Le `sellerId` du produit.
* **`categorie_id`** (BIGINT, Foreign Key $\rightarrow$ `categories.id`) : Le `category` de rattachement.
* **`titre`** (VARCHAR) : Titre de l'annonce (`title`).
* **`description`** (TEXT) : (`description`).
* **`prix`** (DECIMAL) : Prix ferme (`price`).
* **`etat`** (ENUM: `Neuf`, `Occasion`) : **Critique** pour les filtres (`condition`).
* **`localisation`** (VARCHAR, Nullable) : La position spécifique de ce produit (`location`).
* **`variations_possibles`** (JSON, Nullable) : Les choix (Tailles/Couleurs).
* **`statut`** (ENUM: `actif`, `reserve`, `vendu`) : Gère la règle métier D de visibilité ou d'exclusion.
* **`created_at`, `updated_at`, `deleted_at`** (Soft Delete).

### 5️⃣ Table `images_produit`
**Rôle :** L'UI affiche une `image` principale, mais le backend se doit de gérer un carrousel pour l'évolutivité.
* **`id`** (BIGINT, Primary Key)
* **`produit_id`** (BIGINT, Foreign Key $\rightarrow$ `produits.id`)
* **`chemin_image`** (VARCHAR) : L'URL complète.
* **`is_principale`** (BOOLEAN) : Permet de définir facilement la photo de couverture demandée par la propriété `Product.image`.

### 6️⃣ Table `commandes` (Orders)
**Rôle :** Transaction financière (Ou Entente financière dans le chat).
* **`id`** (BIGINT, Primary Key)
* **`client_id`** (BIGINT, Foreign Key $\rightarrow$ `users.id`)
* **`boutique_id`** (BIGINT, Foreign Key $\rightarrow$ `boutiques.id`)
* **`prix_total`** (DECIMAL).
* **`methode_livraison`** (ENUM: `retrait_boutique`, `livraison_vendeur`).
* **`methode_paiement`** (ENUM: `especes`, `transfert_mobile`).
* **`statut`** (ENUM: `en_attente`, `confirmee_par_vendeur`, `annulee_par_client`, `refusee_par_vendeur`, `terminee`).
* **`created_at`, `updated_at`**.

### 7️⃣ Table `lignes_commande` (Order_Items)
**Rôle :** Détail de la commande (Panier validé).
* **`id`** (BIGINT, Primary Key)
* **`commande_id`** (BIGINT, Foreign Key $\rightarrow$ `commandes.id`)
* **`produit_id`** (BIGINT, Foreign Key $\rightarrow$ `produits.id`)
* **`prix_unitaire_achat`** (DECIMAL) : Copie du prix (pour ne pas casser l'historique quand le vendeur change de prix plus tard).
* **`variations_choisies`** (JSON, Nullable) : Les critères sélectionnés par l'acheteur potentiel.

### 8️⃣ Table `conversations` (`Conversation` dans l'UI)
**Rôle :** Énorme mise à jour par rapport à l'UI ! La conversation se raccroche formellement à un vendeur et un produit unique !
* **`id`** (BIGINT, Primary Key)
* **`client_id`** (BIGINT, Foreign Key $\rightarrow$ `users.id`)
* **`boutique_id`** (BIGINT, Foreign Key $\rightarrow$ `boutiques.id`) : C'est le `sellerId` dans l'UI.
* **`produit_id`** (BIGINT, Foreign Key $\rightarrow$ `produits.id`) : C'est le `productId` dans l'UI de la conversation !
* **`commande_id`** (BIGINT, Nullable, Foreign Key $\rightarrow$ `commandes.id`) : Transition fonctionnelle vers la commande.
* **`created_at`**
* **`updated_at`** : Obligatoire pour populer `lastMessage` visuel (tri des chats récents).

### 9️⃣ Table `messages` (`ChatMessage` dans l'UI)
**Rôle :** Contenu dynamique des chats.
* **`id`** (BIGINT, Primary Key)
* **`conversation_id`** (BIGINT, Foreign Key $\rightarrow$ `conversations.id`)
* **`expediteur_id`** (BIGINT, Foreign Key $\rightarrow$ `users.id`) : Gère habilement la propriété front `isMe`.
* **`contenu_texte`** (TEXT, Nullable) : `content`.
* **`capture_url_piece_jointe`** (VARCHAR, Nullable).
* **`lu_a`** (TIMESTAMP, Nullable) : Déclenche le flag pour le compteur front.
* **`created_at`** : Gère la propriété `timestamp`.

### 🔟 Table `favoris`
**Rôle :** Likes sur un produit (`isFavorite`).
* **`id`** (BIGINT, Primary Key)
* **`user_id`** (BIGINT, Foreign Key $\rightarrow$ `users.id`)
* **`produit_id`** (BIGINT, Foreign Key $\rightarrow$ `produits.id`)
* **`created_at`**.

### 1️⃣1️⃣ Table `notifications` (`AppNotification` dans l'UI)
**Rôle :** Notification système en direct.
* Construit nativement avec le moteur asynchrone Laravel (`Illuminate\Notifications`).
* Les Data JSON contiennent :
  * `type` : `'message'`, `'order'`, `'like'` (Gère l'assignation d'icônes côté App).
  * `title` : Titre gras de la notif.
  * `body` : Description textuelle.
* `read_at` (Timestamp natif Laravel) traduit parfaitement la variable front `isRead`.

### 1️⃣2️⃣ Table `villes`
**Rôle :** Liste des villes supportées par l'app (ex: Lomé, Avepozo, Baguida).
* **`id`** (BIGINT, Primary Key)
* **`nom`** (VARCHAR, Unique) : Nom de la ville.

### 1️⃣3️⃣ Table `quartiers`
**Rôle :** Subdivisions des villes pour la sélection rapide.
* **`id`** (BIGINT, Primary Key)
* **`ville_id`** (BIGINT, Foreign Key $\rightarrow$ `villes.id`)
* **`nom`** (VARCHAR) : Nom du quartier.

### 1️⃣4️⃣ Table `adresses` (Relations Polymorphes)
**Rôle :** Mutualiser les adresses pour les utilisateurs et les boutiques. Les coordonnées GPS précises (pinched by user) sont séparées du quartier pour les recherches.
* **`id`** (BIGINT, Primary Key)
* **`addressable_id`** (BIGINT) : ID de l'utilisateur ou de la boutique.
* **`addressable_type`** (VARCHAR) : Le modèle (`App\Models\User` ou `App\Models\Boutique`).
* **`quartier_id`** (BIGINT, Foreign Key $\rightarrow$ `quartiers.id`)
* **`details`** (TEXT, Nullable) : Indications de direction de la maison (Appartement, Rue).
* **`latitude`** (DECIMAL, Nullable) : Placement exact sur la Map fait par l'utilisateur.
* **`longitude`** (DECIMAL, Nullable) : Placement exact sur la Map fait par l'utilisateur.

### 1️⃣5️⃣ Table `category_user` (Préférences de l'App)
**Rôle :** Table pivot stockant les centres d'intérêt de l'utilisateur (utile pour l'algorithme de recommandation et requise lors de la configuration du profil).
* **`user_id`** (BIGINT, Foreign Key $\rightarrow$ `users.id`)
* **`category_id`** (BIGINT, Foreign Key $\rightarrow$ `categories.id`)

---



## ⚡ 3. STATUT ACTUEL DE LA MISSION

Ton application (ses écrans, son design fictif sur `mock_data.dart`, et ses classes objets Flutter sur `models.dart`) a été passée au crible et décortiquée intégralement. Il n'y a plus aucun doute possible : la base de données présentée ci-dessus connectera parfaitement à 100% avec les écrans frontend existants. Chaque champ (Condition, Note du vendeur, Vitesse de réponse, Chat Lié au Produit, Emojis des catégories) est là.
