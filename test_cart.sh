#!/bin/bash

# Configuration
API_URL="https://127.0.0.1:8002"
EMAIL="test@example.com"
PASSWORD="test123"

# Couleurs pour les messages
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

# Fonction pour afficher les messages
print_message() {
    echo -e "${GREEN}$1${NC}"
}

print_error() {
    echo -e "${RED}$1${NC}"
}

# Fonction pour vérifier la réponse
check_response() {
    if [ "$1" -eq "$2" ]; then
        print_message "✅ $3"
    else
        print_error "❌ $3 (Code attendu: $2, Code reçu: $1)"
    fi
}

# Authentification
print_message "🔑 Authentification..."
TOKEN=$(curl -s -k -X POST "$API_URL/api/login_check" \
    -H "Content-Type: application/json" \
    -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}" | jq -r '.token')

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    print_error "❌ Échec de l'authentification"
    exit 1
fi

print_message "✅ Authentification réussie"

# Test 1: Récupérer le panier vide
print_message "\n📦 Test 1: Récupérer le panier vide"
RESPONSE=$(curl -s -k -w "\n%{http_code}" "$API_URL/api/cart" \
    -H "Authorization: Bearer $TOKEN")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

check_response "$HTTP_CODE" 200 "Récupération du panier vide"
echo "Réponse: $BODY"

# Test 2: Ajouter un produit au panier
print_message "\n📦 Test 2: Ajouter un produit au panier"
RESPONSE=$(curl -s -k -w "\n%{http_code}" "$API_URL/api/cart/products/3" \
    -H "Authorization: Bearer $TOKEN" \
    -X POST)
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

check_response "$HTTP_CODE" 200 "Ajout d'un produit au panier"
echo "Réponse: $BODY"

# Test 3: Vérifier le panier avec le produit
print_message "\n📦 Test 3: Vérifier le panier avec le produit"
RESPONSE=$(curl -s -k -w "\n%{http_code}" "$API_URL/api/cart" \
    -H "Authorization: Bearer $TOKEN")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

check_response "$HTTP_CODE" 200 "Vérification du panier avec produit"
echo "Réponse: $BODY"

# Test 4: Tenter d'ajouter un produit en rupture de stock
print_message "\n📦 Test 4: Tenter d'ajouter un produit en rupture de stock"
RESPONSE=$(curl -s -k -w "\n%{http_code}" "$API_URL/api/cart/products/999" \
    -H "Authorization: Bearer $TOKEN" \
    -X POST)
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

check_response "$HTTP_CODE" 400 "Tentative d'ajout d'un produit en rupture de stock"
echo "Réponse: $BODY"

# Test 5: Retirer un produit du panier
print_message "\n📦 Test 5: Retirer un produit du panier"
RESPONSE=$(curl -s -k -w "\n%{http_code}" "$API_URL/api/cart/products/3" \
    -H "Authorization: Bearer $TOKEN" \
    -X DELETE)
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

check_response "$HTTP_CODE" 200 "Retrait d'un produit du panier"
echo "Réponse: $BODY"

# Test 6: Vérifier le panier vide après retrait
print_message "\n📦 Test 6: Vérifier le panier vide après retrait"
RESPONSE=$(curl -s -k -w "\n%{http_code}" "$API_URL/api/cart" \
    -H "Authorization: Bearer $TOKEN")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

check_response "$HTTP_CODE" 200 "Vérification du panier vide après retrait"
echo "Réponse: $BODY"

# Test 7: Ajouter plusieurs produits au panier
print_message "\n📦 Test 7: Ajouter plusieurs produits au panier"
for i in 3 4 5; do
    RESPONSE=$(curl -s -k -w "\n%{http_code}" "$API_URL/api/cart/products/$i" \
        -H "Authorization: Bearer $TOKEN" \
        -X POST)
    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | sed '$d')
    
    check_response "$HTTP_CODE" 200 "Ajout du produit $i au panier"
done

# Test 8: Valider le panier
print_message "\n📦 Test 8: Valider le panier"
RESPONSE=$(curl -s -k -w "\n%{http_code}" "$API_URL/api/cart/validate" \
    -H "Authorization: Bearer $TOKEN" \
    -X POST)
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

check_response "$HTTP_CODE" 200 "Validation du panier"
echo "Réponse: $BODY"

# Test 9: Vérifier que le panier est vide après validation
print_message "\n📦 Test 9: Vérifier que le panier est vide après validation"
RESPONSE=$(curl -s -k -w "\n%{http_code}" "$API_URL/api/cart" \
    -H "Authorization: Bearer $TOKEN")
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

check_response "$HTTP_CODE" 200 "Vérification du panier vide après validation"
echo "Réponse: $BODY"

# Test 10: Tenter de valider un panier vide
print_message "\n📦 Test 10: Tenter de valider un panier vide"
RESPONSE=$(curl -s -k -w "\n%{http_code}" "$API_URL/api/cart/validate" \
    -H "Authorization: Bearer $TOKEN" \
    -X POST)
HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | sed '$d')

check_response "$HTTP_CODE" 400 "Tentative de validation d'un panier vide"
echo "Réponse: $BODY"

print_message "\n✨ Tests terminés !" 