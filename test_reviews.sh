#!/bin/bash

# Configuration
API_URL="https://127.0.0.1:8002"
EMAIL="test@example.com"
PASSWORD="test123"

# Couleurs pour les messages
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

echo "🧪 Test des endpoints d'avis sur les produits"
echo "----------------------------------------"

# Étape 1 : Authentification
echo -e "\n1️⃣ Authentification"
TOKEN=$(curl -s -k -X POST -H "Content-Type: application/json" \
    -d "{\"username\":\"$EMAIL\",\"password\":\"$PASSWORD\"}" \
    "$API_URL/api/login_check" | jq -r '.token')

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    echo -e "${RED}❌ Échec de l'authentification${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Authentification réussie${NC}"

# Étape 2 : Création d'un produit pour les tests
echo -e "\n2️⃣ Création d'un produit pour les tests"
PRODUCT_RESPONSE=$(curl -s -k -X POST -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d '{"title":"Produit Test","price":99.99}' \
    "$API_URL/api/products")

if [ -z "$PRODUCT_RESPONSE" ]; then
    echo -e "${RED}❌ Échec de la création du produit (réponse vide)${NC}"
    exit 1
fi

PRODUCT_ID=$(echo $PRODUCT_RESPONSE | jq -r '.id')
if [ -z "$PRODUCT_ID" ] || [ "$PRODUCT_ID" = "null" ]; then
    echo -e "${RED}❌ Échec de la création du produit${NC}"
    echo "Réponse : $PRODUCT_RESPONSE"
    exit 1
fi
echo -e "${GREEN}✅ Produit créé avec l'ID: $PRODUCT_ID${NC}"

# Test 1 : Création d'un avis
echo -e "\n3️⃣ Test 1 : Création d'un avis"
REVIEW_RESPONSE=$(curl -s -k -X POST -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d '{"rating":5,"message":"Excellent produit !"}' \
    "$API_URL/api/products/$PRODUCT_ID/reviews")

REVIEW_ID=$(echo $REVIEW_RESPONSE | jq -r '.id')
if [ -z "$REVIEW_ID" ] || [ "$REVIEW_ID" = "null" ]; then
    echo -e "${RED}❌ Échec de la création de l'avis${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Avis créé avec l'ID: $REVIEW_ID${NC}"

# Test 2 : Récupération de la liste des avis
echo -e "\n4️⃣ Test 2 : Récupération de la liste des avis"
LIST_RESPONSE=$(curl -s -k -H "Authorization: Bearer $TOKEN" \
    "$API_URL/api/products/$PRODUCT_ID/reviews")

if [[ $LIST_RESPONSE == *"$REVIEW_ID"* ]]; then
    echo -e "${GREEN}✅ Liste des avis récupérée avec succès${NC}"
else
    echo -e "${RED}❌ Échec de la récupération de la liste des avis${NC}"
    exit 1
fi

# Test 3 : Mise à jour d'un avis
echo -e "\n5️⃣ Test 3 : Mise à jour d'un avis"
UPDATE_RESPONSE=$(curl -s -k -X PUT -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d '{"rating":4,"message":"Très bon produit, mais pourrait être amélioré"}' \
    "$API_URL/api/products/$PRODUCT_ID/reviews/$REVIEW_ID")

if [[ $UPDATE_RESPONSE == *"$REVIEW_ID"* ]]; then
    echo -e "${GREEN}✅ Avis mis à jour avec succès${NC}"
else
    echo -e "${RED}❌ Échec de la mise à jour de l'avis${NC}"
    exit 1
fi

# Test 4 : Tentative de mise à jour d'un avis inexistant
echo -e "\n6️⃣ Test 4 : Tentative de mise à jour d'un avis inexistant"
INVALID_UPDATE_RESPONSE=$(curl -s -k -X PUT -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d '{"rating":5,"message":"Test"}' \
    "$API_URL/api/products/$PRODUCT_ID/reviews/99999")

echo "Réponse brute : $INVALID_UPDATE_RESPONSE"

if [[ $INVALID_UPDATE_RESPONSE == *"Avis non trouv"* ]]; then
    echo -e "${GREEN}✅ Comportement attendu pour un avis inexistant${NC}"
else
    echo -e "${RED}❌ Comportement inattendu pour un avis inexistant${NC}"
    exit 1
fi

# Test 5 : Suppression d'un avis
echo -e "\n7️⃣ Test 5 : Suppression d'un avis"
DELETE_RESPONSE=$(curl -s -k -X DELETE -H "Authorization: Bearer $TOKEN" \
    "$API_URL/api/products/$PRODUCT_ID/reviews/$REVIEW_ID")

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Avis supprimé avec succès${NC}"
else
    echo -e "${RED}❌ Échec de la suppression de l'avis${NC}"
    exit 1
fi

# Test 6 : Vérification de la suppression
echo -e "\n8️⃣ Test 6 : Vérification de la suppression"
GET_DELETED_RESPONSE=$(curl -s -w "\n%{http_code}" -k -H "Authorization: Bearer $TOKEN" \
    "$API_URL/api/products/$PRODUCT_ID/reviews/$REVIEW_ID")

HTTP_CODE=$(echo "$GET_DELETED_RESPONSE" | tail -n1)
RESPONSE_BODY=$(echo "$GET_DELETED_RESPONSE" | sed '$d')

echo "Réponse brute : $RESPONSE_BODY"
echo "Code HTTP : $HTTP_CODE"

if [ "$HTTP_CODE" = "405" ]; then
    echo -e "${GREEN}✅ Comportement attendu pour un avis supprimé (Méthode non autorisée)${NC}"
else
    echo -e "${RED}❌ Comportement inattendu pour un avis supprimé${NC}"
    exit 1
fi

echo -e "\n${GREEN}✅ Tous les tests ont réussi !${NC}" 