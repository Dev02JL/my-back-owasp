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
    -d "{\"username\":\"$EMAIL\",\"password\":\"$PASSWORD\"}" | jq -r '.token')

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    print_error "❌ Échec de l'authentification"
    exit 1
fi

print_message "✅ Authentification réussie"

# Création des produits
print_message "\n📦 Création des produits nécessaires"
for i in {1..5}; do
    RESPONSE=$(curl -s -k -w "\n%{http_code}" "$API_URL/api/products" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Content-Type: application/json" \
        -d "{\"title\":\"Produit $i\",\"price\":$((i * 100))}" \
        -X POST)
    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | sed '$d')
    
    if [[ $BODY == *"Produit $i"* ]] && [ "$HTTP_CODE" -eq 201 ]; then
        print_message "✅ Création du produit $i"
    else
        print_error "❌ Échec de la création du produit $i"
        print_error "Réponse: $BODY"
        print_error "Code HTTP: $HTTP_CODE"
        exit 1
    fi
done

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

echo "🔍 Test des routes du contrôleur de commandes"

# 1. Connexion de l'utilisateur
echo -e "\n${GREEN}1. Connexion de l'utilisateur${NC}"
TOKEN=$(curl -s -k -X POST -H "Content-Type: application/json" \
    -d "{\"username\":\"$EMAIL\",\"password\":\"$PASSWORD\"}" \
    "$API_URL/api/login_check" | jq -r '.token')

if [ -z "$TOKEN" ] || [ "$TOKEN" = "null" ]; then
    echo -e "${RED}❌ Échec de la connexion${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Connexion réussie${NC}"

# 2. Création des produits
echo -e "\n${GREEN}2. Création des produits${NC}"
PRODUCT1=$(curl -s -k -X POST -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d '{"title":"Produit 1","price":99.99}' "$API_URL/api/products" | jq -r '.id')
PRODUCT2=$(curl -s -k -X POST -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d '{"title":"Produit 2","price":149.99}' "$API_URL/api/products" | jq -r '.id')
PRODUCT3=$(curl -s -k -X POST -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d '{"title":"Produit 3","price":199.99}' "$API_URL/api/products" | jq -r '.id')

if [ -z "$PRODUCT1" ] || [ "$PRODUCT1" = "null" ]; then
    echo -e "${RED}❌ Échec de la création des produits${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Produits créés avec succès${NC}"

# 3. Test de l'ajout d'un produit au panier
echo -e "\n${GREEN}3. Test de l'ajout d'un produit au panier${NC}"
RESPONSE=$(curl -s -k -X POST -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart/products/$PRODUCT1")
echo -e "${GREEN}✅ Ajout du produit au panier${NC}"
echo "Réponse: $RESPONSE"

# 4. Test de la récupération du panier
echo -e "\n${GREEN}4. Test de la récupération du panier${NC}"
RESPONSE=$(curl -s -k -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart")
echo -e "${GREEN}✅ Récupération du panier${NC}"
echo "Réponse: $RESPONSE"

# 5. Test de la modification de la quantité
echo -e "\n${GREEN}5. Test de la modification de la quantité${NC}"
RESPONSE=$(curl -s -k -X PUT -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" -d "{\"quantity\":2}" "$API_URL/api/cart/products/$PRODUCT1")
echo -e "${GREEN}✅ Modification de la quantité${NC}"
echo "Réponse: $RESPONSE"

# 6. Test de la récupération du panier après modification
echo -e "\n${GREEN}6. Test de la récupération du panier après modification${NC}"
RESPONSE=$(curl -s -k -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart")
echo -e "${GREEN}✅ Vérification du panier après modification${NC}"
echo "Réponse: $RESPONSE"

# 7. Test du retrait d'un produit
echo -e "\n${GREEN}7. Test du retrait d'un produit${NC}"
RESPONSE=$(curl -s -k -X DELETE -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart/products/$PRODUCT1")
echo -e "${GREEN}✅ Retrait d'un produit du panier${NC}"
echo "Réponse: $RESPONSE"

# 8. Test de la vérification du panier vide
echo -e "\n${GREEN}8. Test de la vérification du panier vide${NC}"
RESPONSE=$(curl -s -k -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart")
echo -e "${GREEN}✅ Vérification du panier vide après retrait${NC}"
echo "Réponse: $RESPONSE"

# 9. Test de l'ajout de plusieurs produits
echo -e "\n${GREEN}9. Test de l'ajout de plusieurs produits${NC}"
curl -s -k -X POST -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart/products/$PRODUCT1" > /dev/null
echo -e "${GREEN}✅ Ajout du produit 1 au panier${NC}"
curl -s -k -X POST -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart/products/$PRODUCT2" > /dev/null
echo -e "${GREEN}✅ Ajout du produit 2 au panier${NC}"
curl -s -k -X POST -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart/products/$PRODUCT3" > /dev/null
echo -e "${GREEN}✅ Ajout du produit 3 au panier${NC}"

# 10. Test de la validation du panier
echo -e "\n${GREEN}10. Test de la validation du panier${NC}"
RESPONSE=$(curl -s -k -X POST -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart/validate")
echo -e "${GREEN}✅ Validation du panier${NC}"
echo "Réponse: $RESPONSE"

# 11. Test de la vérification du panier vide après validation
echo -e "\n${GREEN}11. Test de la vérification du panier vide après validation${NC}"
RESPONSE=$(curl -s -k -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart")
echo -e "${GREEN}✅ Vérification du panier vide après validation${NC}"
echo "Réponse: $RESPONSE"

# 12. Test de la tentative de validation d'un panier vide
echo -e "\n${GREEN}12. Test de la tentative de validation d'un panier vide${NC}"
RESPONSE=$(curl -s -k -X POST -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart/validate")
echo -e "${GREEN}✅ Tentative de validation d'un panier vide${NC}"
echo "Réponse: $RESPONSE"

# 6. Test de la modification de la quantité d'un produit
echo -e "\n${GREEN}6. Test de la modification de la quantité d'un produit${NC}"
RESPONSE=$(curl -s -k -X PUT -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -d '{"quantity": 2}' "$API_URL/api/cart/products/40")
echo -e "${GREEN}✅ Modification de la quantité d'un produit${NC}"
echo "Réponse: $RESPONSE"

# 7. Test de la récupération du panier après modification
echo -e "\n${GREEN}7. Test de la récupération du panier après modification${NC}"
RESPONSE=$(curl -s -k -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart")
echo -e "${GREEN}✅ Vérification du panier après modification${NC}"
echo "Réponse: $RESPONSE"

# 8. Test du retrait d'un produit
echo -e "\n${GREEN}8. Test du retrait d'un produit${NC}"
RESPONSE=$(curl -s -k -X DELETE -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart/products/40")
echo -e "${GREEN}✅ Retrait d'un produit du panier${NC}"
echo "Réponse: $RESPONSE"

# 9. Test de la vérification du panier vide
echo -e "\n${GREEN}9. Test de la vérification du panier vide${NC}"
RESPONSE=$(curl -s -k -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart")
echo -e "${GREEN}✅ Vérification du panier vide après retrait${NC}"
echo "Réponse: $RESPONSE"

# 10. Test de l'ajout de plusieurs produits
echo -e "\n${GREEN}10. Test de l'ajout de plusieurs produits${NC}"
RESPONSE=$(curl -s -k -X POST -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart/products/40")
echo -e "${GREEN}✅ Ajout du produit 1 au panier${NC}"
echo "Réponse: $RESPONSE"

RESPONSE=$(curl -s -k -X POST -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart/products/41")
echo -e "${GREEN}✅ Ajout du produit 2 au panier${NC}"
echo "Réponse: $RESPONSE"

RESPONSE=$(curl -s -k -X POST -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart/products/42")
echo -e "${GREEN}✅ Ajout du produit 3 au panier${NC}"
echo "Réponse: $RESPONSE"

# 11. Test de la validation du panier
echo -e "\n${GREEN}11. Test de la validation du panier${NC}"
RESPONSE=$(curl -s -k -X POST -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart/validate")
echo -e "${GREEN}✅ Validation du panier${NC}"
echo "Réponse: $RESPONSE"

# 12. Test de la vérification du panier vide après validation
echo -e "\n${GREEN}12. Test de la vérification du panier vide après validation${NC}"
RESPONSE=$(curl -s -k -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart")
echo -e "${GREEN}✅ Vérification du panier vide après validation${NC}"
echo "Réponse: $RESPONSE"

# 13. Test de la tentative de validation d'un panier vide
echo -e "\n${GREEN}13. Test de la tentative de validation d'un panier vide${NC}"
RESPONSE=$(curl -s -k -X POST -H "Authorization: Bearer $TOKEN" "$API_URL/api/cart/validate")
echo -e "${GREEN}✅ Tentative de validation d'un panier vide${NC}"
echo "Réponse: $RESPONSE"

echo -e "\n${GREEN}✅ Tests terminés${NC}" 