# nStatus-backend-php

## Requirements
### php.ini
```extension=sockets```

## schema examples
### configuration
```json
{
  "servers": [
    {
      "id": "2942b660-bf5f-4b60-93e3-c9a09a6df0c6",
      "name": "myServer"
    },
    {
      "id": "51910a25-1dc3-4037-92d1-d5acfdff74b1",
      "name": "myOtherServer"
    },
    {
      "id": "4647c075-3dac-45ae-9715-5bdcb27433ad",
      "name": "unassigned",
      "default": true
    }
  ],
  "services": [
    {
      "id": "5740a923-ab0e-4fbd-831d-f46943a906b2",
      "name": "myService1",
      "icon": "database",
      "server": "2942b660-bf5f-4b60-93e3-c9a09a6df0c6"
    },
    {
      "id": "b41ea15c-5d08-49b6-9107-2ea91edc86d9",
      "name": "myService2",
      "icon": "hdd",
      "server": "2942b660-bf5f-4b60-93e3-c9a09a6df0c6"
    },
    {
      "id": "94c27065-d709-4a19-8746-414a18c0f1f9",
      "name": "myOtherService1",
      "icon": "database",
      "server": "51910a25-1dc3-4037-92d1-d5acfdff74b1"
    },
    {
      "id": "13c5f6f6-b90e-4ff9-ab42-d0a67366c96e",
      "name": "aService1",
      "icon": "server",
      "server": "00000000-0000-0000-0000-000000000000"
    },
    {
      "id": "2b49fdd7-5399-4e9c-b554-8cb34eb5cc37",
      "name": "aService2",
      "icon": "server"
    }
  ]
}
```

### endpoints
#### /servers
```json
[
    {
      "id": "2942b660-bf5f-4b60-93e3-c9a09a6df0c6",
      "name": "myServer",
      "services": [
        {
          "id": "5740a923-ab0e-4fbd-831d-f46943a906b2",
          "name": "myService1",
          "icon": "database",
          "statusHistory": {
            "2023-09-26T10:00:00+02:00": "reachable",
            "2023-09-25T14:30:00+02:00": "warning",
            "2023-09-24T09:15:00+02:00": "unreachable",
            "2023-09-23T16:45:00+02:00": "reachable",
            "2023-09-22T11:20:00+02:00": "warning"
          }
        },
        {
          "id": "b41ea15c-5d08-49b6-9107-2ea91edc86d9",
          "name": "myService2",
          "icon": "hdd",
          "statusHistory": {
            "2023-09-28T08:10:00+02:00": "reachable",
            "2023-09-25T09:30:00+02:00": "unreachable",
            "2023-09-24T18:00:00+02:00": "warning"
          }
        }
      ]
    },
    {
      "id": "51910a25-1dc3-4037-92d1-d5acfdff74b1",
      "name": "myOtherServer",
      "services": [
        {
          "id": "94c27065-d709-4a19-8746-414a18c0f1f9",
          "name": "myOtherService1",
          "icon": "database",
          "statusHistory": {
            "2023-09-28T10:00:00+02:00": "unreachable",
            "2023-09-27T15:30:00+02:00": "reachable",
            "2023-09-26T14:45:00+02:00": "unreachable",
            "2023-09-25T11:30:00+02:00": "reachable"
          }
        }
      ]
    },
    {
      "id": "4647c075-3dac-45ae-9715-5bdcb27433ad",
      "name": "unassigned",
      "services": [
        {
          "id": "13c5f6f6-b90e-4ff9-ab42-d0a67366c96e",
          "name": "aService1",
          "icon": "server",
          "statusHistory": {
            "2023-09-28T10:00:00+02:00": "unreachable",
            "2023-09-27T15:30:00+02:00": "reachable",
            "2023-09-26T14:45:00+02:00": "unreachable",
            "2023-09-25T11:30:00+02:00": "reachable"
          }
        },
        {
          "id": "2b49fdd7-5399-4e9c-b554-8cb34eb5cc37",
          "name": "aService2",
          "icon": "server",
          "statusHistory": {
            "2023-09-28T10:00:00+02:00": "unreachable",
            "2023-09-27T15:30:00+02:00": "reachable",
            "2023-09-26T14:45:00+02:00": "unreachable",
            "2023-09-25T11:30:00+02:00": "reachable"
          }
        }
      ]
    }
]
```
#### /services/94c27065-d709-4a19-8746-414a18c0f1f9
```json
{
  "id": "94c27065-d709-4a19-8746-414a18c0f1f9",
  "name": "myOtherService1",
  "icon": "database",
  "statusHistory": {
    "2023-09-28T10:00:00+02:00": "unreachable",
    "2023-09-27T15:30:00+02:00": "reachable",
    "2023-09-26T14:45:00+02:00": "unreachable",
    "2023-09-25T11:30:00+02:00": "reachable",
    "2023-09-25T09:30:00+02:00": "unreachable",
    "2023-09-24T18:00:00+02:00": "warning",
    "2023-09-23T14:15:00+02:00": "unreachable",
    "2023-09-22T11:20:00+02:00": "reachable",
    "2023-09-21T10:00:00+02:00": "warning",
    "2023-09-20T17:45:00+02:00": "reachable",
    "2023-09-19T16:30:00+02:00": "unreachable"
  }
}
```