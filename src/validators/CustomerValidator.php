<?php
declare(strict_types=1);

final class CustomerValidator
{
    public static function validateCreate(array $data): array
    {
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $address = trim((string) ($data['address'] ?? ''));

        // Kiểm tra họ tên
        if ($fullName === '') {
            throw new InvalidArgumentException(
                'Ho ten khach hang la bat buoc'
            );
        }

        if (mb_strlen($fullName) > 150) {
            throw new InvalidArgumentException(
                'Ho ten khong duoc vuot qua 150 ky tu'
            );
        }

        // Kiểm tra số điện thoại
        if ($phone === '') {
            throw new InvalidArgumentException(
                'So dien thoai la bat buoc'
            );
        }

        if (!preg_match('/^\+?[0-9\s().-]+$/', $phone)) {
            throw new InvalidArgumentException(
                'So dien thoai chua ky tu khong hop le'
            );
        }

        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === null ||
            strlen($digits) < 8 ||
            strlen($digits) > 15) {
            throw new InvalidArgumentException(
                'So dien thoai phai co tu 8 den 15 chu so'
            );
        }

        // Kiểm tra email nếu được cung cấp
        if ($email !== '' &&
            !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(
                'Email khong dung dinh dang'
            );
        }

        if (strlen($email) > 150) {
            throw new InvalidArgumentException(
                'Email qua dai'
            );
        }

        if (mb_strlen($address) > 255) {
            throw new InvalidArgumentException(
                'Dia chi qua dai'
            );
        }

        return [
            'full_name' => $fullName,
            'phone' => $phone,
            'email' => $email === '' ? null : $email,
            'address' => $address === '' ? null : $address
        ];
    }
    // Validation cho cap nhat ho so khach hang
    public static function validateUpdate(array $data): array
    {
        $fullName = $data['full_name'] ?? null;

        if (!is_string($fullName)) {
            throw new InvalidArgumentException(
                'Ho ten khach hang la bat buoc'
            );
        }

        $fullName = trim($fullName);

        if ($fullName === '' || mb_strlen($fullName) > 150) {
            throw new InvalidArgumentException(
                'Ho ten phai co tu 1 den 150 ky tu'
            );
        }

        $email = $data['email'] ?? null;

        if ($email !== null && !is_string($email)) {
            throw new InvalidArgumentException(
                'Email khong hop le'
            );
        }

        $email = $email === null ? '' : trim($email);

        if ($email !== '' &&
            (!filter_var($email, FILTER_VALIDATE_EMAIL)
             || strlen($email) > 150)) {
            throw new InvalidArgumentException(
                'Email khong dung dinh dang'
            );
        }

        $address = $data['address'] ?? null;

        if ($address !== null && !is_string($address)) {
            throw new InvalidArgumentException(
                'Dia chi khong hop le'
            );
        }

        $address = $address === null ? '' : trim($address);

        if (mb_strlen($address) > 255) {
            throw new InvalidArgumentException(
                'Dia chi khong duoc vuot qua 255 ky tu'
            );
        }

        return [
            'full_name' => $fullName,
            'email' => $email === '' ? null : $email,
            'address' => $address === '' ? null : $address
        ];
    }

}
