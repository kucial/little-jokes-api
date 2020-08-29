# Little Jokes 小小笑话

小小笑话是一个简单的笑话API服务。当前处于API开发阶段，未提供真实数据

## 功能列表

- Feed
    - 热门列表，最新列表，随机列表
- 用户认证
    - 注册、登录
- Post
    - 获取文章（笑话）
    - 收藏、取消收藏
    - 内容举报
    - 用户收藏列表
- 收藏管理 
    - 归档、取消归档
    
## TODO

- [ ] 举报管理

## 开发部署

此项目使用 [laradock](https://laradock.io/) 作为开发环境，需要使用的服务有 `mysql`, `nginx`, `php-fpm`, `redis`

1. Clone 项目

    ```bash
    # 将 laradock submodule 一并拉取
    git clone --recurse-submodules 
    ```

2. 进入项目目录，创建项目的 `.env`

    ```bash
    cp .env.exmaple .env
    ```

    对 `.env` 进行修改

3. 进入 `<项目目录>/laradock`，创建 laradock 的 `.env`

    ```bash
    cp env.example .env
    ``` 

4. 启动 `laradock` 服务

    ```bash
    docker-compose up -d nginx mysql php-fpm redis
    ```

5. 进入 `laradock` 的 `workspace`

    ```bash
    docker-compose exec workspace bash
    ```

6. 初始化数据库

    ```bash
    # 在 workspace 的 bash 中
    root@<container-id>: /var/www# php artisan migrate
    ```

7. 生成测试数据

    ```bash
    # 在 workspace 的 bash 中
    root@<container-id>: /var/www# php artisan db:seed
    ```


laradock 的使用方式可以查阅 [laradock 文档](https://laradock.io/documentation/)
    
## API

Postman collection 地址：https://www.postman.com/collections/ecae4bac5f42feba213a

可在 Postman > import > link ，粘贴地址，> Continue 进行导入 


| 名称 | 描述 | 地址 |
| --- | --- |  --- |
| [Post.get](#postget) | 获取文章内容 |  GET `/api/posts/{id}` |
| [Post.like](#postlike) | 收藏文章 | POST `/api/posts/{id}/_like` |
| [Post.unlike](#postunlike) | 取消收藏文章 | POST `/api/posts/{id}/_unlike` |
| [Post.report](#postreport) | 举报文章 | POST `/api/posts/{id}/_report` |
| [Post.vote](#postvote) | 投票 | POST `/api/posts/{id}/_vote` |
| [Post.userLiked](#postuserliked) | 用户收藏列表 | GET `/api/users/{id}/liked-posts` |
| | | |
| [PostLike.archive](#postlikearchive) | 归档收藏记录 | POST `/api/likes/{id}/_archive` |
| [PostLike.archive](#postlikeunarchive) | 取消归档收藏记录 | POST `/api/likes/{id}/_unarchive` |
| | | |
| [Feed.latest](#feedlatest) | 最新文章列表 | GET `/api/feed/latest` |
| [Feed.hottest](#feedlatest) | 最热文章列表 | GET `/api/feed/hottest` |
| [Feed.random](#feedlatest) | 随机文章列表 | GET `/api/feed/random` |
| | | |
| [Auth.Register.sendPhoneCode](#authregistersendphonecode) | 发送注册验证码 | POST `/api/auth/register/send_phone_code` |
| [Auth.Register.withPhoneCode](#authregisterwithphonecode) | 手机验证码注册 | GET `/api/auth/register/with_phone_code` |
| [Auth.Login.sendPhoneCode](#authloginsendphonecode) | 发送登录验证码 | POST `/api/auth/login/send_phone_code` |
| [Auth.Login.withPhoneCode](#authloginwithphonecode) | 手机验证码登录 | POST `/api/auth/login/with_phone_code` |
| [Auth.Login.withPhonePassword](#authloginwithphonepassword) | 手机密码登录 | POST `/api/auth/login/with_phone_password` |


### API 公共规范

API Response 的数据结果：

```typescript
interface ResourceResponse<T> {
    data: T,
    meta?: Object,
}

interface CollectionResponse<T> {
    data: Array<T>,
    links?: {
        first: String,
        last: String,
        prev?: String,
        next?: String,
    },
    meta?: {
        current_page: Number,
        from: Number,
        last_page: Number,
        path: String, // API request path
        per_page: Number,
        to: Number,
        total: Number
    }
}

interface ErrorResponse {
    code: String;
    message: String; 
    data?: Object;
}
```

---

### Post

资源数据结构

```typescript
class Post {
    id: Number;
    content: String;
    created_at: DateString;
    updated_at: DateString;
    blocked_at?: DateString;
    like?: PostLike; // 当前用户的点赞信息
}
```

---

#### Post.get

获取文章详情

GET `/api/posts/{id}`,

**Path 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| id | Number | 是 | 文章ID |

**Response**

```typescript
// 200
interface PostResource {
    data: Post,
}
```

---

#### Post.like

收藏文章

POST `/api/posts/{id}/_like`

**Path 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| id | Number | 是 | 文章ID |

**Response**

```typescript
// 200 
interface PostResource {
    data: Post,
}

// 400 code: HAS_LIKED

// 401 code: NOT_AUTHENTICATED
```

---

#### Post.unlike

取消收藏文章

POST `/api/posts/{id}/_unlike`

**Path 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| id | Number | 是 | 文章ID |

**Response**

```typescript
// 200 
interface PostResource {
    data: Post,
}

// 400 code: NOT_LIKED

// 401 code: NOT_AUTHENTICATED
```

---

#### Post.report

举报文章内容

POST `/api/posts/${id}/_report`

**Path 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| id | Number | 是 | 文章ID |

**Body 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| description | String | 是 | 举报原因描述，最大长度：255 |


**Response**
```typescript
class PostReport {
  id: Number;
  post_id: Number;
  user_id: Number;
  description: String;
  
}
// 200
{
    data: PostReport
}

// 401 code: NOT_AUTHENTICATED 未认证

// 422 code: VALIDATION_FAILED 表单验证错误
```

---

#### Post.vote

对文章进行投票，一个用户只能投一次票，最后一次投票的结果会覆盖上一次的投票

POST `/api/posts/{id}/_vote`

**Path 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| id | Number | 是 | 文章ID |

**Body 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| vote_type | Enum | 是 | 投票类型，点赞(up)或踩(down)，{ UP_VOTE: 1, DOWN_VOTE: -1} |

**Response**

```typescript
enum VoteType {
  UP_VOTE = 1,
  DOWN_VOTE = -1,
}

// 200 
{
    data: {
        id: Number;
        user_id: Number;
        post_id: Number;
        vote_type: VoteType;
    }
}
```

---

#### Post.userLiked 

获取用户收获的文章列表。权限：

GET `/api/users/{id}/liked-posts`

**Query 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| page | Number | 否 | 页码，默认值： `1`|
| page_size | Number | 否 | 分页大小，默认值： `20`|

**Response**

```typescript
// 200 CollectionResponse<PostResource>

// 403 code: NOT_AUTHORIZED
```

---

### PostLike

用户收藏文章的记录

```typescript
class PostLike {
    id: Number;
    user_id: Number;
    post_ik: Number;
    created_at: DateString;
    updated_at: DateString;
    archived_at: DateString;
}
```

---

#### PostLike.archive

将收藏记录归档

POST `/api/likes/{id}/_archive`

**Path 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| id | Number | 是 | Like ID |

**Response**

```typescript
// 200
{
    data: PostLike;
}

// 400 code: HAS_ARCHIVED  收藏记录已归档

// 401 code: NOT_AUTHENTICATED 未认证

// 403 code: NOT_AUTHORIZED 无权进行归档
```

---

#### PostLike.unarchive

取消收藏记录的归档

POST `/api/likes/{id}/_unarchive`

**Path 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| id | Number | 是 | Like ID |

**Response**

```typescript
// 200
{
    data: PostLike;
}

// 400 code: NOT_ARCHIVED  收藏记录未归档

// 401 code: NOT_AUTHENTICATED 未认证

// 403 code: NOT_AUTHORIZED 无权进行归档
```
---

### Feed

#### Feed.latest

获取最新的文章列表

GET `/api/feed/latest`

**Query 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| page | Number | 否 | 页码，默认值： `1`|
| page_size | Number | 否 | 分页大小，默认值： `20`|

**Response**

```typescript
interface FeedResponse {
    data: Array<Post>,
    meta: PaginationMeta,
}
```

---

#### Feed.hottest

获取最热的文章列表

GET `/api/feed/hottest`

**Query 参数**

参数选项同 `Feed.latest`

**Response**

返回数据结构，同 `Feed.latest`

---

#### Feed.random

获取随机排列的文章列表

GET `/api/feed/random`

**Query 参数**

| 名称 | 类型 |描述 | 
| --- | --- | --- |
| page | Number? | 页码，默认值： `1`|
| page_size | Number? | 分页大小，默认值： `20`|
| seed | Number | 随机Seed |

**Response**

返回数据结构，同 `Feed.latest`

---

### Auth

用户登录认证相关。认证成功后，统一返回的数据结构：

```typescript
class User {
    id: Number;
    name: String;
    created_at: DateString;
}
interface AuthSuccessResponse {
  data: User,
  meta: {
      api_token: String,
  }
}
```

---

#### 用户注册

用户可以通过手机号码进行注册，注册步骤与接口调用顺序

1. 用户填写手机号码
2. 用户请求验证码, [POST] `/api/auth/register/send_phone_code`
3. 用户输入验证吗，并提交 [POST] `/api/auth/register/with_phone_code`

---

#### Auth.Register.sendPhoneCode

请求发送注册验证码

[POST] `/api/auth/register/send_phone_code`

**Body 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| phone | Number | 是 | e164格式的手机号码，例：中国手机号码 -- `+861327722xxxx` |

**Response**

```typescript
// 200 <EMPTY RESPONSE>

// 422 code: REGISTERED 已注册
```

---

#### Auth.Register.withPhoneCode

使用手机号码及验证码完成注册

[POST] `/api/auth/register/with_phone_code`

**Body 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| phone | Number | 是 | e164格式的手机号码，例：中国手机号码 -- `+861327722xxxx` |
| code | Number | 是 | 手机上收到的验证码 |

**Response**

```typescript
// 200 注册成功， AuthSuccessResponse

// 422 code: REGISTERED

// 422 code: INVALID_CODE
```

---

#### 登录

登录方式有两种，分别是 "验证码登录" 与 "密码登录"

使用"验证码登录"时，需要先调用请求验证码登录的接口，再验证验证码

---

#### Auth.Login.sendPhoneCode

请求登录验证码

[POST] `/api/auth/login/send_phone_code`

**Body 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| phone | Number | 是 | e164格式的手机号码，例：中国手机号码 -- `+861327722xxxx` |

**Response**

```typescript
// 200 <EMPTY RESPONSE>

// 422 code: VALIDATION_FAILED
```

---

#### Auth.Login.withPhoneCode

使用验证码进行登录

[POST] `/api/auth/login/with_phone_code`

**Body 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| phone | Number | 是 | e164格式的手机号码，例：中国手机号码 -- `+861327722xxxx` |
| code | Number | 是 | 手机上收到的验证码 |

**Response**

```typescript
// 200 登录成功， AuthSuccessResponse

// 422 code: INVALID_CODE 

// 422 code: VALIDATION_FAILED

```

---

#### Auth.Login.withPhonePassword

使用验证码登录，一般用于测试账号的登录

[POST] `/api/auth/login/with_phone_password`

**Body 参数**

| 名称 | 类型 | 必填 | 描述 | 
| --- | --- | --- | --- |
| phone | Number | 是 | e164格式的手机号码，例：中国手机号码 -- `+861327722xxxx` |
| password | String | 是 | 账号密码 |

**Response**

```typescript
// 200 登录成功， AuthSuccessResponse

// 422 code: INVALID_CREDENTIALS 账号或密码不正确 

// 422 code: VALIDATION_FAILED

```
