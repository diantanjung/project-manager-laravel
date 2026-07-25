# Menangani False Positive Intelephense di Laravel dan Pest

> Panduan singkat untuk warning IDE/static analyzer yang muncul, tetapi kode Laravel/Pest sebenarnya valid dan test tetap pass.

## Prinsip

Jangan langsung ubah logic aplikasi hanya karena Intelephense menandai error. Pastikan dulu dengan:

```bash
php artisan test --compact
```

Untuk file tertentu:

```bash
php artisan test --compact tests/Feature/DomainApiTest.php
```

Kalau runtime test pass, cek apakah warning berasal dari pola dinamis Laravel/Pest yang memang sulit dibaca Intelephense.

## Pest: Hindari `$this->...` di Closure Test

Di Pest, closure test memang di-bind ke `Tests\TestCase`, tetapi Intelephense kadang tidak mengenali `$this` sebagai Laravel test case.

### HTTP Helpers

Jika Intelephense menandai:

```php
$this->withToken($token)->getJson('/api/v1/projects');
$this->postJson('/api/v1/projects', $payload);
$this->patchJson('/api/v1/projects/1', $payload);
$this->deleteJson('/api/v1/projects/1');
```

Gunakan helper Pest Laravel:

```php
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

withToken($token)->getJson('/api/v1/projects');
postJson('/api/v1/projects', $payload);
patchJson('/api/v1/projects/1', $payload);
deleteJson('/api/v1/projects/1');
```

Kalau butuh chaining token + request:

```php
withToken($token)
    ->getJson('/api/v1/projects')
    ->assertOk();
```

### Database / Model Assertions

Jika Intelephense menandai:

```php
$this->assertModelExists($project);
$this->assertModelMissing($project);
```

Gunakan helper Pest Laravel:

```php
use function Pest\Laravel\assertModelExists;
use function Pest\Laravel\assertModelMissing;

assertModelExists($project);
assertModelMissing($project);
```

### Alternatif Jika Tetap Pakai `$this`

Bisa tambahkan PHPDoc di dalam closure, tapi ini lebih berisik:

```php
test('example', function () {
    /** @var Tests\TestCase $this */

    $this->getJson('/api/v1/projects')->assertOk();
});
```

Prefer helper Pest Laravel untuk test baru.

## Laravel Query Builder: `whereDate()` dan `count()`

Intelephense bisa salah membaca overload fluent query builder.

Kode ini valid di Laravel:

```php
Task::query()
    ->whereDate('due_date', '<', today())
    ->count();
```

Signature Laravel mendukung default argument:

```php
whereDate($column, $operator, $value = null, $boolean = 'and')
count($columns = '*')
```

Jika ingin meredam warning tanpa mengubah behavior, buat argumen default eksplisit:

```php
Task::query()
    ->whereDate('due_date', '<', today(), 'and')
    ->count('*');
```

## Eloquent Model Methods: `delete()`

Intelephense kadang menandai method seperti:

```php
$project->delete();
```

Padahal `delete()` berasal dari `Illuminate\Database\Eloquent\Model`.

Opsi penanganan:

1. Biarkan jika test pass dan PHPStan/Larastan tidak protes.
2. Tambahkan PHPDoc di model hanya jika warning sering mengganggu:

```php
/**
 * @method bool|null delete()
 */
class Project extends Model
{
    //
}
```

Jangan ubah logic delete menjadi query manual hanya untuk memuaskan IDE.

## Checklist Saat Ada Warning Intelephense

1. Jalankan test terkait.
2. Cek apakah method berasal dari Laravel dynamic/fluent API.
3. Untuk Pest closure, prefer helper `Pest\Laravel\...`.
4. Untuk query builder, boleh eksplisitkan argumen default jika tidak mengubah behavior.
5. Untuk Eloquent model method, pakai PHPDoc seperlunya.
6. Jangan refactor domain logic hanya karena warning IDE.
