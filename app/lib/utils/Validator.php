<?php
class Validator {
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function required($value) {
        return !empty(trim($value));
    }

    public static function minLength($value, $min) {
        return strlen(trim($value)) >= $min;
    }

    public static function maxLength($value, $max) {
        return strlen(trim($value)) <= $max;
    }

    public static function isNumeric($value) {
        return is_numeric($value);
    }

    public static function inArray($value, $array) {
        return in_array($value, $array);
    }

    public static function validatePdf($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file uploaded'];
        }

        if ($file['size'] > MAX_FILE_SIZE) {
            return ['valid' => false, 'error' => 'File too large. Maximum ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
            return ['valid' => false, 'error' => 'Only PDF files are allowed'];
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            return ['valid' => false, 'error' => 'Invalid file extension'];
        }

        return ['valid' => true];
    }
}
?>
