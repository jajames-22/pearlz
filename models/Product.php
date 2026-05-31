<?php
// models/Product.php

class Product {
    private $conn;
    private $table_name = "Products";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT p.*, c.category_name, 
                         (SELECT image_url FROM Product_Images i WHERE i.product_id = p.product_id ORDER BY is_primary DESC, image_id ASC LIMIT 1) as thumbnail
                  FROM " . $this->table_name . " p 
                  LEFT JOIN Categories c ON p.category_id = c.category_id 
                  ORDER BY p.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE product_id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (category_id, base_sku, name, description, base_price, is_limited_edition, allows_custom_text, custom_text_limit) 
                  VALUES (:category_id, :base_sku, :name, :description, :base_price, :is_limited_edition, :allows_custom_text, :custom_text_limit)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':base_sku', $data['base_sku']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':base_price', $data['base_price']);
        $stmt->bindParam(':is_limited_edition', $data['is_limited_edition'], PDO::PARAM_INT);
        $stmt->bindParam(':allows_custom_text', $data['allows_custom_text'], PDO::PARAM_INT);
        $stmt->bindParam(':custom_text_limit', $data['custom_text_limit']);

        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET category_id = :category_id, 
                      base_sku = :base_sku, 
                      name = :name, 
                      description = :description, 
                      base_price = :base_price, 
                      is_limited_edition = :is_limited_edition, 
                      allows_custom_text = :allows_custom_text, 
                      custom_text_limit = :custom_text_limit 
                  WHERE product_id = :id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':category_id', $data['category_id']);
        $stmt->bindParam(':base_sku', $data['base_sku']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':base_price', $data['base_price']);
        $stmt->bindParam(':is_limited_edition', $data['is_limited_edition'], PDO::PARAM_INT);
        $stmt->bindParam(':allows_custom_text', $data['allows_custom_text'], PDO::PARAM_INT);
        $stmt->bindParam(':custom_text_limit', $data['custom_text_limit']);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE product_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    // --- VARIANT & IMAGE MANAGEMENT ---

    public function getVariants($product_id) {
        $query = "SELECT v.*, i.image_id, i.image_url 
                  FROM Product_Variants v 
                  LEFT JOIN Product_Images i ON v.variant_id = i.variant_id AND i.image_type = 'variant'
                  WHERE v.product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addVariant($product_id, $sku, $name, $price, $stock, $image_path) {
        try {
            $this->conn->beginTransaction();

            $query = "INSERT INTO Product_Variants (product_id, variant_sku, variant_name, price_override, stock_quantity) 
                      VALUES (:pid, :sku, :name, :price, :stock)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':pid', $product_id);
            $stmt->bindParam(':sku', $sku);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock', $stock);
            $stmt->execute();
            
            $variant_id = $this->conn->lastInsertId();

            if ($image_path) {
                $img_query = "INSERT INTO Product_Images (product_id, variant_id, image_url, image_type) 
                              VALUES (:pid, :vid, :url, 'variant')";
                $img_stmt = $this->conn->prepare($img_query);
                $img_stmt->bindParam(':pid', $product_id);
                $img_stmt->bindParam(':vid', $variant_id);
                $img_stmt->bindParam(':url', $image_path);
                $img_stmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function updateVariant($variant_id, $sku, $name, $price, $stock, $image_path = null) {
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE Product_Variants SET variant_sku = :sku, variant_name = :name, price_override = :price, stock_quantity = :stock WHERE variant_id = :vid";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':vid', $variant_id);
            $stmt->bindParam(':sku', $sku);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock', $stock);
            $stmt->execute();

            if ($image_path !== null) {
                $del_img = "DELETE FROM Product_Images WHERE variant_id = :vid";
                $del_stmt = $this->conn->prepare($del_img);
                $del_stmt->bindParam(':vid', $variant_id);
                $del_stmt->execute();

                $get_pid = "SELECT product_id FROM Product_Variants WHERE variant_id = :vid";
                $pid_stmt = $this->conn->prepare($get_pid);
                $pid_stmt->bindParam(':vid', $variant_id);
                $pid_stmt->execute();
                $pid = $pid_stmt->fetchColumn();

                $img_query = "INSERT INTO Product_Images (product_id, variant_id, image_url, image_type) VALUES (:pid, :vid, :url, 'variant')";
                $img_stmt = $this->conn->prepare($img_query);
                $img_stmt->bindParam(':pid', $pid);
                $img_stmt->bindParam(':vid', $variant_id);
                $img_stmt->bindParam(':url', $image_path);
                $img_stmt->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    public function deleteVariant($variant_id) {
        $query = "DELETE FROM Product_Variants WHERE variant_id = :vid";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':vid', $variant_id);
        return $stmt->execute();
    }

    public function getBaseImages($product_id) {
        $query = "SELECT * FROM Product_Images WHERE product_id = :pid AND variant_id IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $product_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addBaseImage($product_id, $image_path, $is_primary = 0) {
        $query = "INSERT INTO Product_Images (product_id, image_url, image_type, is_primary) 
                  VALUES (:pid, :url, 'feature', :is_primary)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':pid', $product_id);
        $stmt->bindParam(':url', $image_path);
        $stmt->bindParam(':is_primary', $is_primary, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function deleteImage($image_id) {
        $query = "DELETE FROM Product_Images WHERE image_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $image_id);
        return $stmt->execute();
    }
}
?>
