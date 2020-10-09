<?php

return [
    'alipay' => [
        'app_id'         => '2021000118642121',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArfTtowyylxDNWjNIgRtZLQPBgbMUAampA2rHGUJBKAKiUxsyI0WnsA+jdbBOeYUVO/ppEdIde6as3INekl4XN3IzSiapd4BRJlXMR2ISlNd5dmQgedLoafiTucdKF3LW3PzPgsxW82iq0ZQVtdTmVIFSt+62dUkIRmVCqdkVWQfcUeJZtRFxLrjcZnyyEJy/UZeI/D6LjyyR5oeJUnvndyKtyVAsK1mTUB8ohRj0bSyzzUSRXdLydR9CemqntgqCRaBRJ65YI2hpMlwhygoIMdfv06MbzOtJC671PGNJMTCjwIkbWJ/gwNC2Bmfivq60Nx7+8WiWPtEaP0kgIX41KwIDAQAB',
        'private_key'    => 'MIIEowIBAAKCAQEAk9F1zJlj9b+XskSgtqb+MKCnCz8So6YFuhIzfwK4WcAVI7ft5KPPMXpWd7Xl/S+fkekAZ0K8xZU0gSUbdCUdY4vA9zliQTC8h2FPw89xaQ0PN+jdFVtcAfRK2kbemm3DLd1KBDERZ4Fp9s2FM7Z6jzi41QGmdyU4JBcTTK6evUxZ7JT8w4MzEVB+yYzz54r1M/hzTRnpFS9Ieyh3kvgrZeBuPm6ogWL2V1O5ENpT+1zld2y7PjWjWDEtQK7rpQBoTQ4mwQuopGdZlwr5CPnRiW5OJT6eA4+FTgLEhLwXyK8NUxbOoCxaFxy9Y4SUwzam4gezLWbx2IRmozNwGFC+5wIDAQABAoIBAAhu69SXb7+GhcYS6kRhdKEbmUwn9g1GHI+IGE4HvrLIJaybAsSHn/uHqkU8KHnwbJ8rdu1tPk3bfFpd3poav/l88K5qJLPpbugeYimevS6sIxEihPKB+tbVtCuN3Ydb+cW1GVLx5bdNB9mf0hioMYfYSZtFc9TaV8CfXGeEkuPjz9O9ZS3nfVscyiXPIZV4wQPwlhiVj9picFFpZdinStvOFxLQSl7IfWAPot4+5faRnyWZJhsq7SAZu9zRAFbtQJ+rKoiQMJTY5RPiGwR73YM0P9ywtsBlZtki7wFZvnHPvAh8korzM3rDUxQE6jS7p2pzf7o3akhWsy9lRPYnVtECgYEA4MfDUrK71SCoZDSacCOggbc8jvPQ1zjy2OBsmTaVlabqo/YE8+kKLKinWDgdkNU+ktrXm29yUg9w1wYlkoaxLxySX+EFl01ve4jygSkMzgNxYdP9j0c3HN7kRQ4xtPDjoiunYqCdCvacyerkCBDOtFNFIdsvosIHUdBFNyMsVQkCgYEAqFk/+JPHn32fDF45AmOPFQXm0ZO2LVHGWZFxFZxTAfyEEu74LHoGeqITFZviv1nohtZZTcvRDzYRv0nh7zQ/O5dWCZS6eD5snBFNDt+o9RJuXoZ7f6GQZcuiODzFa0t+D5kMwhg5w4Yv/exWc8yActvKs/w2Cte0gRCFKzww4G8CgYAbuSejBH5cK/n42fAOUqaSORpT+0hPsytoik16nBvY6ExaSpaTyrNBjM+O9uTWnyZnkGw1NIqiClt9ebmal9g0mk6HWsaIwbk1QE/AlGKK6ivKyA2m5T6r5eW7iqOg0HES6FVtuaeE2aO+16SmRgRogzisk08NwOaMNabDxSfLmQKBgHxGxo6+qVL37X234OX+kRW38ZktLgNuprpgP9bwO+bvfqBrgRF0U2wcUXJWTaFswdcoTWy81WwhQiCwbfWj4DohkgYooS87BfqAWx5rxdKE9K0bIfgqUOqU1QAm/KYkaL8jAOQX9ix81tjgq0F46ingT1dnDI4chsfwZh5wpLRJAoGBAJZoqwA9VYcPA01JkA2mHKwYr0zjCOzQBTXflik0Fno64SykFkcKPT/k7k741D3GAlcP1bMMfNH9Xc+yl/+WAYs9KzxXJVpj+nxH54ppzZr//n27Zk38HZpp+eoiuMogICs0SF48biEzwKxUqhKvrXUk92jywNB1Z4J150a5oWI9',
        'log'            => [
            'file' => storage_path('logs/alipay.log'),
        ],  
    ],

    'wechat' => [
        'app_id'      => '',
        'mch_id'      => '',
        'key'         => '',
        'cert_client' => '',
        'cert_key'    => '',
        'log'         => [
            'file' => storage_path('logs/wechat_pay.log'),
        ],
    ],
];