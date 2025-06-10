#!/bin/bash

# Configuration
API_URL="https://127.0.0.1:8002"
EMAIL="test@example.com"
PASSWORD="test123"

# Couleurs pour les messages
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

echo "🔍 Test de l'API des produits"
echo "---------------------------"

# Authentification pour obtenir le token
echo -e "\n${GREEN}Étape 1:${NC} Authentification"
RESPONSE=$(curl -s -k -X POST -H "Content-Type: application/json" \
    -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}" \
    "$API_URL/api/login_check")

TOKEN=$(echo $RESPONSE | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "❌ Échec de l'authentification"
    echo "Réponse: $RESPONSE"
    exit 1
fi

echo "✅ Authentification réussie"

# Test 1: Création d'un produit
echo -e "\n${GREEN}Test 1:${NC} Création d'un produit"
CREATE_RESPONSE=$(curl -s -k -X POST "$API_URL/api/products" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d "{\"title\":\"Test Product\",\"price\":19.99}")

if [[ $CREATE_RESPONSE == *"Test Product"* ]]; then
    echo "✅ Produit créé avec succès"
    PRODUCT_ID=$(echo $CREATE_RESPONSE | grep -o '"id":[0-9]*' | cut -d':' -f2)
else
    echo "❌ Échec de la création du produit"
    echo "Réponse: $CREATE_RESPONSE"
    exit 1
fi

# Test 2: Récupération de la liste des produits
echo -e "\n${GREEN}Test 2:${NC} Récupération de la liste des produits"
LIST_RESPONSE=$(curl -s -k -X GET "$API_URL/api/products" \
    -H "Authorization: Bearer $TOKEN")

if [[ $LIST_RESPONSE == *"Test Product"* ]]; then
    echo "✅ Liste des produits récupérée avec succès"
else
    echo "❌ Échec de la récupération de la liste"
    echo "Réponse: $LIST_RESPONSE"
fi

# Test 3: Récupération d'un produit spécifique
echo -e "\n${GREEN}Test 3:${NC} Récupération d'un produit spécifique"
GET_RESPONSE=$(curl -s -k -X GET "$API_URL/api/products/$PRODUCT_ID" \
    -H "Authorization: Bearer $TOKEN")

if [[ $GET_RESPONSE == *"Test Product"* ]]; then
    echo "✅ Produit récupéré avec succès"
else
    echo "❌ Échec de la récupération du produit"
    echo "Réponse: $GET_RESPONSE"
fi

# Test 4: Mise à jour d'un produit
echo -e "\n${GREEN}Test 4:${NC} Mise à jour d'un produit"
UPDATE_RESPONSE=$(curl -s -k -X PUT "$API_URL/api/products/$PRODUCT_ID" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d "{\"title\":\"Updated Product\",\"price\":29.99}")

if [[ $UPDATE_RESPONSE == *"Updated Product"* ]]; then
    echo "✅ Produit mis à jour avec succès"
else
    echo "❌ Échec de la mise à jour du produit"
    echo "Réponse: $UPDATE_RESPONSE"
fi

# Test 5: Suppression d'un produit
echo -e "\n${GREEN}Test 5:${NC} Suppression d'un produit"
DELETE_RESPONSE=$(curl -s -k -X DELETE "$API_URL/api/products/$PRODUCT_ID" \
    -H "Authorization: Bearer $TOKEN")

if [ $? -eq 0 ]; then
    echo "✅ Produit supprimé avec succès"
else
    echo "❌ Échec de la suppression du produit"
    echo "Réponse: $DELETE_RESPONSE"
fi

# Test 6: Tentative de récupération d'un produit supprimé
echo -e "\n${GREEN}Test 6:${NC} Tentative de récupération d'un produit supprimé"
GET_DELETED_RESPONSE=$(curl -s -k -X GET "$API_URL/api/products/$PRODUCT_ID" \
    -H "Authorization: Bearer $TOKEN")

if [[ $GET_DELETED_RESPONSE == *"Produit non trouv"* ]]; then
    echo "✅ Erreur attendue : produit non trouvé"
else
    echo "❌ Comportement inattendu"
    echo "Réponse: $GET_DELETED_RESPONSE"
fi

echo -e "\n${GREEN}Tests terminés${NC}" 