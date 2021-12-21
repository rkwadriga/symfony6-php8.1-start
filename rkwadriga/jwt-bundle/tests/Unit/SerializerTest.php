<?php declare(strict_types=1);
/**
 * Created 2021-12-21
 * Author Dmitry Kushneriov
 */

namespace Rkwadriga\JwtBundle\Tests\Unit;

use Rkwadriga\JwtBundle\Exception\SerializerException;
use Rkwadriga\JwtBundle\Exception\TokenValidatorException;

/**
 * @Run: test rkwadriga/jwt-bundle/tests/Unit/SerializerTest.php
 */
class SerializerTest extends AbstractUnitTestCase
{
    public function testEncode(): void
    {
        $testCases = [
            'RANDOM_LONG_STRING_asdfkxsdlkfjg;lkdfgsdlkgs98qwekxlavfa__skdfaklj7w3i2lkdf{sdfsd}sldjflksjdfsdafsssssssssssszxcvzxcbvd23253245654356_pdsfgdsfgsdhd.sdfgsdflkgskjsdkjv&sdftsdsxdv09:sdfvsew'
                => 'UkFORE9NX0xPTkdfU1RSSU5HX2FzZGZreHNkbGtmamc7bGtkZmdzZGxrZ3M5OHF3ZWt4bGF2ZmFfX3NrZGZha2xqN3czaTJsa2Rme3NkZnNkfXNsZGpmbGtzamRmc2RhZnNzc3Nzc3Nzc3Nzc3p4Y3Z6eGNidmQyMzI1MzI0NTY1NDM1Nl9wZHNmZ2RzZmdzZGhkLnNkZmdzZGZsa2dza2pzZGtqdiZzZGZ0c2RzeGR2MDk6c2RmdnNldw',
            'RANDOM_SHORT_STR' => 'UkFORE9NX1NIT1JUX1NUUg',
            '{"name":"correct_json","data":[1,2,3,4,5]}' => 'eyJuYW1lIjoiY29ycmVjdF9qc29uIiwiZGF0YSI6WzEsMiwzLDQsNV19',
            '{"name":"incorrect_json","data":1,2,3,4,5]}' => 'eyJuYW1lIjoiaW5jb3JyZWN0X2pzb24iLCJkYXRhIjoxLDIsMyw0LDVdfQ',
            '1234567789012345677891234567890123344567789012334456778901233445677890123344567789012334456778901233445677890123344567789012334456778901233445677890123344567789012334456778901233445677890123344567789'
                => 'MTIzNDU2Nzc4OTAxMjM0NTY3Nzg5MTIzNDU2Nzg5MDEyMzM0NDU2Nzc4OTAxMjMzNDQ1Njc3ODkwMTIzMzQ0NTY3Nzg5MDEyMzM0NDU2Nzc4OTAxMjMzNDQ1Njc3ODkwMTIzMzQ0NTY3Nzg5MDEyMzM0NDU2Nzc4OTAxMjMzNDQ1Njc3ODkwMTIzMzQ0NTY3Nzg5MDEyMzM0NDU2Nzc4OTAxMjMzNDQ1Njc3ODkwMTIzMzQ0NTY3Nzg5MDEyMzM0NDU2Nzc4OQ',
            '12345i' => 'MTIzNDVp',
        ];
        $serializer = $this->createSerializerInstance();
        foreach ($testCases as $case => $result) {
            $this->assertSame($result, $serializer->encode($case));
        }
    }

    public function testDecode(): void
    {
        $testCases = [
            'UkFORE9NX0xPTkdfU1RSSU5HX2FzZGZreHNkbGtmamc7bGtkZmdzZGxrZ3M5OHF3ZWt4bGF2ZmFfX3NrZGZha2xqN3czaTJsa2Rme3NkZnNkfXNsZGpmbGtzamRmc2RhZnNzc3Nzc3Nzc3Nzc3p4Y3Z6eGNidmQyMzI1MzI0NTY1NDM1Nl9wZHNmZ2RzZmdzZGhkLnNkZmdzZGZsa2dza2pzZGtqdiZzZGZ0c2RzeGR2MDk6c2RmdnNldw'
                => 'RANDOM_LONG_STRING_asdfkxsdlkfjg;lkdfgsdlkgs98qwekxlavfa__skdfaklj7w3i2lkdf{sdfsd}sldjflksjdfsdafsssssssssssszxcvzxcbvd23253245654356_pdsfgdsfgsdhd.sdfgsdflkgskjsdkjv&sdftsdsxdv09:sdfvsew',
            'UkFORE9NX1NIT1JUX1NUUg' => 'RANDOM_SHORT_STR',
            'eyJuYW1lIjoiY29ycmVjdF9qc29uIiwiZGF0YSI6WzEsMiwzLDQsNV19' => '{"name":"correct_json","data":[1,2,3,4,5]}',
            'eyJuYW1lIjoiaW5jb3JyZWN0X2pzb24iLCJkYXRhIjoxLDIsMyw0LDVdfQ' => '{"name":"incorrect_json","data":1,2,3,4,5]}',
            'MTIzNDU2Nzc4OTAxMjM0NTY3Nzg5MTIzNDU2Nzg5MDEyMzM0NDU2Nzc4OTAxMjMzNDQ1Njc3ODkwMTIzMzQ0NTY3Nzg5MDEyMzM0NDU2Nzc4OTAxMjMzNDQ1Njc3ODkwMTIzMzQ0NTY3Nzg5MDEyMzM0NDU2Nzc4OTAxMjMzNDQ1Njc3ODkwMTIzMzQ0NTY3Nzg5MDEyMzM0NDU2Nzc4OTAxMjMzNDQ1Njc3ODkwMTIzMzQ0NTY3Nzg5MDEyMzM0NDU2Nzc4OQ'
                => '1234567789012345677891234567890123344567789012334456778901233445677890123344567789012334456778901233445677890123344567789012334456778901233445677890123344567789012334456778901233445677890123344567789',
            'MTIzNDVp' => '12345i',
        ];
        $serializer = $this->createSerializerInstance();
        foreach ($testCases as $case => $result) {
            $this->assertSame($result, $serializer->decode($case));
        }
    }

    public function testDecodeInvalidBase64DataException(): void
    {
        $invalidBase64String = "ûï¾møçž\n";

        $this->expectException(SerializerException::class);
        $this->expectExceptionCode(SerializerException::INVALID_BASE64_DATA);
        $this->createSerializerInstance()->decode($invalidBase64String);
    }

    public function testSerialize(): void
    {
        $testCases = [
            'eyJuYW1lIjoiVGVzdF9jYXNlXzEiLCJkYXRhIjpbMSwyLDMsNCw1XX0' => ['name' => 'Test_case_1', 'data' => [1, 2, 3, 4, 5]],
            'eyJuYW1lIjoiVGVzdF9jYXNlXzEiLCJkYXRhIjp7InN0cl9wYXJhbSI6IlN0cmluZyB2YWx1ZSAxIiwiaW50X3BhcmFtIjoxMjM0NSwiZmxvYXRfcGFyYW0iOjMuMTQsImFycmF5X3BhcmFtIjpbMSwyLDMsNCw1LFsxLDIsM11dLCJudWxsX3BhcmFtIjpudWxsfX0'
                => ['name' => 'Test_case_1', 'data' => ['str_param' => 'String value 1', 'int_param' => 12345, 'float_param' => 3.14, 'array_param' => [1, 2, 3, 4, 5, [1, 2, 3]], 'null_param' => null]],
            'WzEsMiwzLDQsNV0' => [1, 2, 3, 4, 5],
            'eyIwIjoxLCIxIjoyLCJzdHJfcGFyYW0iOiJTdHJpbmcgdmFsdWUgMSIsImludF9wYXJhbSI6MTIzNDUsImZsb2F0X3BhcmFtIjozLjE0LCJhcnJheV9wYXJhbSI6WzEsMiwzLDQsNV0sIm51bGxfcGFyYW0iOm51bGwsIjIiOjMsIjMiOm51bGwsIjQiOmZhbHNlLCI1IjowfQ'
                => [1, 2, 'str_param' => 'String value 1', 'int_param' => 12345, 'float_param' => 3.14, 'array_param' => [1, 2, 3, 4 , 5], 'null_param' => null, 3, null, false, 0],
        ];

        $serializer = $this->createSerializerInstance();
        foreach ($testCases as $result => $case) {
            $this->assertSame($result, $serializer->serialize($case));
        }
    }

    public function testDeserialize(): void
    {
        $testCases = [
            'eyJuYW1lIjoiVGVzdF9jYXNlXzEiLCJkYXRhIjpbMSwyLDMsNCw1XX0' => ['name' => 'Test_case_1', 'data' => [1, 2, 3, 4, 5]],
            'eyJuYW1lIjoiVGVzdF9jYXNlXzEiLCJkYXRhIjp7InN0cl9wYXJhbSI6IlN0cmluZyB2YWx1ZSAxIiwiaW50X3BhcmFtIjoxMjM0NSwiZmxvYXRfcGFyYW0iOjMuMTQsImFycmF5X3BhcmFtIjpbMSwyLDMsNCw1LFsxLDIsM11dLCJudWxsX3BhcmFtIjpudWxsfX0'
                => ['name' => 'Test_case_1', 'data' => ['str_param' => 'String value 1', 'int_param' => 12345, 'float_param' => 3.14, 'array_param' => [1, 2, 3, 4 , 5, [1, 2, 3]], 'null_param' => null]],
            'WzEsMiwzLDQsNV0' => [1, 2, 3, 4, 5],
            'eyIwIjoxLCIxIjoyLCJzdHJfcGFyYW0iOiJTdHJpbmcgdmFsdWUgMSIsImludF9wYXJhbSI6MTIzNDUsImZsb2F0X3BhcmFtIjozLjE0LCJhcnJheV9wYXJhbSI6WzEsMiwzLDQsNV0sIm51bGxfcGFyYW0iOm51bGwsIjIiOjMsIjMiOm51bGwsIjQiOmZhbHNlLCI1IjowfQ'
                => [1, 2, 'str_param' => 'String value 1', 'int_param' => 12345, 'float_param' => 3.14, 'array_param' => [1, 2, 3, 4, 5], 'null_param' => null, 3, null, false, 0],
        ];

        $serializer = $this->createSerializerInstance();
        foreach ($testCases as $case => $result) {
            $this->assertSame($result, $serializer->deserialiaze($case));
        }
    }

    public function testDeserializeInvalidBase64DataException(): void
    {
        $invalidBase64String = "ûï¾møçž\n";
        $this->expectException(SerializerException::class);
        $this->expectExceptionCode(SerializerException::INVALID_BASE64_DATA);
        $this->createSerializerInstance()->deserialiaze($invalidBase64String);
    }

    public function testDeserializeInvalidJsonDataException(): void
    {
        $invalidJsonString = base64_encode('{"name":"Invalid_json_data", "data":[1,2,3}');
        $this->expectException(SerializerException::class);
        $this->expectExceptionCode(SerializerException::INVALID_JSON_DATA);
        $this->createSerializerInstance()->deserialiaze($invalidJsonString);
    }

    public function testImplode(): void
    {
        $testCases = [
            'eyJhbGciOiJTSEEyNTYiLCJ0eXAiOiJKV1QiLCJzdWIiOiJhY2Nlc3MifQ.eyJjcmVhdGVkIjoxNjQwMDg4Mjg2LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ.MTUzYWYwYjYyYjg1YjJmZjc0MjdiMjFiZTQ4OTY5NTQ2NmI3ZWJmOWQ0M2FmNGM5NTViYmU0OWZlMDhhOTRkNg'
                => ['eyJhbGciOiJTSEEyNTYiLCJ0eXAiOiJKV1QiLCJzdWIiOiJhY2Nlc3MifQ', 'eyJjcmVhdGVkIjoxNjQwMDg4Mjg2LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ', 'MTUzYWYwYjYyYjg1YjJmZjc0MjdiMjFiZTQ4OTY5NTQ2NmI3ZWJmOWQ0M2FmNGM5NTViYmU0OWZlMDhhOTRkNg'],
            'eyJhbGciOiJTSEEyNTYiLCJ0eXAiOiJKV1QiLCJzdWIiOiJyZWZyZXNoIn0.eyJjcmVhdGVkIjoxNjQwMDg4Mjg2LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ.YWVjZDc2MjBhOTdlZDg2NTI1ZTQxOTcwNWFiOTkyMDFlMWIyMTIxMGFiZDExNjc4ZTBkMWRkYTJhMjZhMmIzMQ'
                => ['eyJhbGciOiJTSEEyNTYiLCJ0eXAiOiJKV1QiLCJzdWIiOiJyZWZyZXNoIn0', 'eyJjcmVhdGVkIjoxNjQwMDg4Mjg2LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ', 'YWVjZDc2MjBhOTdlZDg2NTI1ZTQxOTcwNWFiOTkyMDFlMWIyMTIxMGFiZDExNjc4ZTBkMWRkYTJhMjZhMmIzMQ'],
            'eyJhbGciOiJTSEE1MTIiLCJ0eXAiOiJKV1QiLCJzdWIiOiJhY2Nlc3MifQ.eyJjcmVhdGVkIjoxNjQwMDg4NTE0LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ.NzYwMzk0MmZmNDU5YWZjMjVhOTVmMDIwMDI1NGQ0MTZmZWYwNzQwM2E1NGI3NWU2NzU2YzNiMTc1MGRlMjZmZTcyMGEzN2IxZmNlNDA1MmQyNzdiZGQzODk3ZGI1NDgzM2I1MjMzN2UwYmU4MDM0ZGRlYmQ4MzVjYTg4ZWNhNmY'
                => ['eyJhbGciOiJTSEE1MTIiLCJ0eXAiOiJKV1QiLCJzdWIiOiJhY2Nlc3MifQ', 'eyJjcmVhdGVkIjoxNjQwMDg4NTE0LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ', 'NzYwMzk0MmZmNDU5YWZjMjVhOTVmMDIwMDI1NGQ0MTZmZWYwNzQwM2E1NGI3NWU2NzU2YzNiMTc1MGRlMjZmZTcyMGEzN2IxZmNlNDA1MmQyNzdiZGQzODk3ZGI1NDgzM2I1MjMzN2UwYmU4MDM0ZGRlYmQ4MzVjYTg4ZWNhNmY'],
            'eyJhbGciOiJTSEE1MTIiLCJ0eXAiOiJKV1QiLCJzdWIiOiJyZWZyZXNoIn0.eyJjcmVhdGVkIjoxNjQwMDg4NTE0LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ.NTk4MTI4MjNmNjQ0YzU2YmQ1YWYyY2E2ZThiZjM1MzI1M2E5ZjA1ZDFkNjRmNDdhNTYzNzJjYWJjN2YxNjdhNjA4ZDZkZGE3YTE0MmI1OGI1NzkwM2E0OGEyNTVhMDY1MzYyMzFlMzc5YTkxMmY5MDVhYjMxYzk2MjkzNzNjYjc'
                => ['eyJhbGciOiJTSEE1MTIiLCJ0eXAiOiJKV1QiLCJzdWIiOiJyZWZyZXNoIn0', 'eyJjcmVhdGVkIjoxNjQwMDg4NTE0LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ', 'NTk4MTI4MjNmNjQ0YzU2YmQ1YWYyY2E2ZThiZjM1MzI1M2E5ZjA1ZDFkNjRmNDdhNTYzNzJjYWJjN2YxNjdhNjA4ZDZkZGE3YTE0MmI1OGI1NzkwM2E0OGEyNTVhMDY1MzYyMzFlMzc5YTkxMmY5MDVhYjMxYzk2MjkzNzNjYjc'],
            'eyJhbGciOiJTSEEyNTYiLCJ0eXAiOiJKV1QiLCJzdWIiOiJhY2Nlc3MifQ.MANY.PARTS.CASE.eyJhbGciOiJTSEEyNTYiLCJ0eXAiOiJKV1QiLCJzdWIiOiJyZWZyZXNoIn0'
                => ['eyJhbGciOiJTSEEyNTYiLCJ0eXAiOiJKV1QiLCJzdWIiOiJhY2Nlc3MifQ', 'MANY', 'PARTS', 'CASE', 'eyJhbGciOiJTSEEyNTYiLCJ0eXAiOiJKV1QiLCJzdWIiOiJyZWZyZXNoIn0'],
        ];
        $serializer = $this->createSerializerInstance();
        foreach ($testCases as $result => $case) {
            $this->assertSame($result, $serializer->implode(...$case));
        }
    }

    public function testExplode(): void
    {
        $testCases = [
            'eyJhbGciOiJTSEEyNTYiLCJ0eXAiOiJKV1QiLCJzdWIiOiJhY2Nlc3MifQ.eyJjcmVhdGVkIjoxNjQwMDg4Mjg2LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ.MTUzYWYwYjYyYjg1YjJmZjc0MjdiMjFiZTQ4OTY5NTQ2NmI3ZWJmOWQ0M2FmNGM5NTViYmU0OWZlMDhhOTRkNg'
                => ['eyJhbGciOiJTSEEyNTYiLCJ0eXAiOiJKV1QiLCJzdWIiOiJhY2Nlc3MifQ', 'eyJjcmVhdGVkIjoxNjQwMDg4Mjg2LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ', 'MTUzYWYwYjYyYjg1YjJmZjc0MjdiMjFiZTQ4OTY5NTQ2NmI3ZWJmOWQ0M2FmNGM5NTViYmU0OWZlMDhhOTRkNg'],
            'eyJhbGciOiJTSEEyNTYiLCJ0eXAiOiJKV1QiLCJzdWIiOiJyZWZyZXNoIn0.eyJjcmVhdGVkIjoxNjQwMDg4Mjg2LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ.YWVjZDc2MjBhOTdlZDg2NTI1ZTQxOTcwNWFiOTkyMDFlMWIyMTIxMGFiZDExNjc4ZTBkMWRkYTJhMjZhMmIzMQ'
                => ['eyJhbGciOiJTSEEyNTYiLCJ0eXAiOiJKV1QiLCJzdWIiOiJyZWZyZXNoIn0', 'eyJjcmVhdGVkIjoxNjQwMDg4Mjg2LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ', 'YWVjZDc2MjBhOTdlZDg2NTI1ZTQxOTcwNWFiOTkyMDFlMWIyMTIxMGFiZDExNjc4ZTBkMWRkYTJhMjZhMmIzMQ'],
            'eyJhbGciOiJTSEE1MTIiLCJ0eXAiOiJKV1QiLCJzdWIiOiJhY2Nlc3MifQ.eyJjcmVhdGVkIjoxNjQwMDg4NTE0LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ.NzYwMzk0MmZmNDU5YWZjMjVhOTVmMDIwMDI1NGQ0MTZmZWYwNzQwM2E1NGI3NWU2NzU2YzNiMTc1MGRlMjZmZTcyMGEzN2IxZmNlNDA1MmQyNzdiZGQzODk3ZGI1NDgzM2I1MjMzN2UwYmU4MDM0ZGRlYmQ4MzVjYTg4ZWNhNmY'
                => ['eyJhbGciOiJTSEE1MTIiLCJ0eXAiOiJKV1QiLCJzdWIiOiJhY2Nlc3MifQ', 'eyJjcmVhdGVkIjoxNjQwMDg4NTE0LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ', 'NzYwMzk0MmZmNDU5YWZjMjVhOTVmMDIwMDI1NGQ0MTZmZWYwNzQwM2E1NGI3NWU2NzU2YzNiMTc1MGRlMjZmZTcyMGEzN2IxZmNlNDA1MmQyNzdiZGQzODk3ZGI1NDgzM2I1MjMzN2UwYmU4MDM0ZGRlYmQ4MzVjYTg4ZWNhNmY'],
            'eyJhbGciOiJTSEE1MTIiLCJ0eXAiOiJKV1QiLCJzdWIiOiJyZWZyZXNoIn0.eyJjcmVhdGVkIjoxNjQwMDg4NTE0LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ.NTk4MTI4MjNmNjQ0YzU2YmQ1YWYyY2E2ZThiZjM1MzI1M2E5ZjA1ZDFkNjRmNDdhNTYzNzJjYWJjN2YxNjdhNjA4ZDZkZGE3YTE0MmI1OGI1NzkwM2E0OGEyNTVhMDY1MzYyMzFlMzc5YTkxMmY5MDVhYjMxYzk2MjkzNzNjYjc'
                => ['eyJhbGciOiJTSEE1MTIiLCJ0eXAiOiJKV1QiLCJzdWIiOiJyZWZyZXNoIn0', 'eyJjcmVhdGVkIjoxNjQwMDg4NTE0LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ', 'NTk4MTI4MjNmNjQ0YzU2YmQ1YWYyY2E2ZThiZjM1MzI1M2E5ZjA1ZDFkNjRmNDdhNTYzNzJjYWJjN2YxNjdhNjA4ZDZkZGE3YTE0MmI1OGI1NzkwM2E0OGEyNTVhMDY1MzYyMzFlMzc5YTkxMmY5MDVhYjMxYzk2MjkzNzNjYjc'],

        ];
        $serializer = $this->createSerializerInstance();
        foreach ($testCases as $case => $result) {
            $this->assertSame($result, $serializer->explode($case));
        }
    }

    public function testExplodeInvalidFormatException(): void
    {
        $invalidDataString = 'eyJhbGciOiJTSEEyNTYiLCJ0eXAiOiJKV1QiLCJzdWIiOiJhY2Nlc3MifQ.eyJjcmVhdGVkIjoxNjQwMDg4Mjg2LCJlbWFpbCI6InVzZXJAbWFpbC5jb20ifQ.MTUzYWYwYjYyYjg1YjJmZjc0MjdiMjFiZTQ4OTY5NTQ2NmI3ZWJmOWQ0M2FmNGM5NTViYmU0OWZlMDhhOTRkNg.eyJhbGciOiJTSEEyNTYiLCJ0eXAiOiJKV1QiLCJzdWIiOiJhY2Nlc3MifQ';

        $this->expectException(TokenValidatorException::class);
        $this->expectExceptionCode(TokenValidatorException::INVALID_FORMAT);
        $this->createSerializerInstance()->explode($invalidDataString);
    }
}