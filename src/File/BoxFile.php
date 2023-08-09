<?php

namespace PrasadChinwal\Box\File;

use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use PrasadChinwal\Box\Box;
use PrasadChinwal\Box\Contracts\FileContract;
use PrasadChinwal\Box\Traits\CanCollaborate;
use PrasadChinwal\Box\Traits\CanShare;
use PrasadChinwal\Box\Traits\CanWatermark;
use PrasadChinwal\Box\Traits\HasVersions;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BoxFile implements FileContract
{
    use CanCollaborate;
    use CanShare;
    use HasVersions;
    use CanWatermark;

    protected string $endpoint = 'https://api.box.com/2.0/files/';

    protected string $uploadUrl = 'https://upload.box.com/api/2.0/files/content';

    private string $sharedLinkUrl = 'https://api.box.com/2.0/shared_items/';

    protected ?string $id = null;

    protected string $sharedLink = '';
    protected ?string $sharedLinkPassword = null;

    protected Collection $result;

    private Box $box;

    /**
     * @throws Exception
     */
    public function __construct(Box $box)
    {
        $this->box = $box;
    }

    /**
     * @param string $id
     * @return BoxFile
     * @throws Exception
     */
    public static function whereId(string $id): BoxFile
    {
        $class = new self(new Box());
        $class->id = $id;
        return $class;
    }

    /**
     * @throws Exception
     */
    public function info(): Collection
    {
        return Http::withToken($this->box->getAccessToken())
            ->get($this->endpoint . $this->id)
            ->throwUnlessStatus(200)
            ->collect();
    }

    /**
     * @return BinaryFileResponse
     * @throws FileNotFoundException
     */
    public function downloadFile(): BinaryFileResponse
    {
        $response = Http::withToken($this->box->getAccessToken())->sink(storage_path('/app/test.pdf'))
            ->get($this->endpoint . $this->id . '/content');

        if ($response->noContent()) {
            throw new FileNotFoundException('The file information was not found!');
        }

        if (!$response->successful()) {
            throw new Exception('Could not find File!');
        }

        return response()->download(storage_path('/app/test.pdf'));
    }

    /**
     * @throws RequestException
     */
    public function thumbnail(string $extension, int $width = null, int $height = null): Collection
    {
        if (!in_array($extension, ['.png', '.jpg'])) {
            throw new ValidationException('File extension not supported!');
        }

        return Http::withToken($this->box->getAccessToken())
            ->get($this->endpoint . $this->id . '/thumbnail' . $extension)
            ->throwUnlessStatus([200,201])
            ->collect();
    }

    /**
     * @param array $attributes
     * @return Collection
     * @throws Exception
     */
    public function copy(array $attributes = []): Collection
    {
        $response = Http::asForm()
            ->withToken($this->box->getAccessToken())
            ->asJson()
            ->post($this->endpoint . $this->id . '/copy', $attributes);

        if ($response->noContent()) {
            throw new \PHPUnit\Runner\Exception('The file information was not found!');
        }
        if (!$response->successful()) {
            throw new Exception('Could not find File information!');
        }
        return $response->collect();
    }

    /**
     * @param array $attributes
     * @return Collection
     * @throws Exception
     */
    public function update(array $attributes = []): Collection
    {
        return Http::asForm()
            ->withToken($this->box->getAccessToken())
            ->asJson()
            ->put($this->endpoint . $this->id, $attributes)
            ->throwUnlessStatus(200)->collect();
    }

    /**
     * @param string $filepath
     * @param string $filename
     * @param array $attributes
     * @return Collection
     * @throws RequestException
     */
    public function create(string $filepath, string $filename, array $attributes = []): Collection
    {
        return Http::asMultipart()
            ->withToken($this->box->getAccessToken())
            ->attach('file', file_get_contents($filepath), $filename)
            ->post($this->uploadUrl, $attributes)
            ->throwUnlessStatus(201)
            ->collect();

//        curl -i -X POST 'https://upload.box.com/api/2.0/files/content'
//          -H 'Authorization: Bearer 1!9a_uTXCiur0sUmhGFA8Lqq821SOQXI0Lk0Opcw-VEg4oqHwupOYTre-UV1Ejqud3uarT3eL8wJXcH9eKqcnLIL-CNqRl_3ymyQFzr5sarWyE6dpYcSdLNg465J9iexvqmWJqAooqbLVTNKnklphEd1C8JGoi9XsLgrfFyykY2uWsCzChtLSHND6XmQT5e-B3AhahdWNoSbedl8-rdxB8L3oy-PmDKHTsWJh7mYjS8YHmfRxDMmX6AUGaUncbCmQmsk1HwN7oNqwNf_NTjZvFSq7hf78dV8z_wd5XVHNzwoq6C_X5KdmOLEMkSoF28HMl_NvsUDiMOSxYV81k1En4HTTelIUGufRlt8hUAHRv5C2eme3FN8C_w2Y5LIIcDSDACdOCTzp0NfbpfTpcnDSfzLN6HfPa7jASQHUPGvTjhjUbXvQivZKEKiAeOyi4ach51hruGJ3ojgXzycOn3GMruTZTsD8I3Pp0tkyZNom6o9jshpkk8M6rC-9T7OLqXlkiM75MVnpSLdNntisWSD2x-r0h6iTMEEbeyTFAJPnldE6Npy0q6ECnfXL1xTwPFE6ZrmKVPlQEnw..'
//          -H 'Content-Type: multipart/form-data'
//          -F attributes='{"name":"Petition_333.pdf", "parent":{"id":"93237200893"}}'
//          -F file=@/Users/prasadchinwal/Documents/uis-projects/package/storage/app/Petition_333.pdf
    }

    /**
     * @throws Exception
     */
    public function delete(): Response
    {
        $response = Http::withToken($this->box->getAccessToken())
            ->delete($this->endpoint . $this->id)
            ->throwUnlessStatus(302);

        if ($response->noContent()) {
            return new Response('File has been deleted successfully');
        }
        throw new Exception('Could not delete File!');
    }

    /**
     * @param array $attributes
     * @return Collection
     * @throws RequestException
     */
    public function createSharedLink(array $attributes): Collection
    {
        /**
         * TODO:
         * - Implement option for unshared_at
         */
        return Http::asForm()
            ->asJson()
            ->withToken($this->box->getAccessToken())
            ->put($this->endpoint . $this->id, $attributes)
            ->throwUnlessStatus(200)
            ->collect();
    }

}
