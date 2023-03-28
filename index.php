<?php


/**
 * In this example, the Factory Method pattern provides an interface for creating social network connectors,
 * which can be used to log in to the network, create posts and potentially perform other activities
 * — and all of this without coupling the client code to specific classes of the particular social network.
 */
namespace FactoryMethod\RealWorldExample;

/**
 * The Creator declares a factory method that can be used as a substitution for
 * the direct constructor calls of products, for instance:
 *
 * - Before: $p = new FacebookConnector();
 * - After: $p = $this->getSocialNetwork;
 *
 * This allows changing the type of the product being created by
 * SocialNetworkPoster's subclasses.
 */
abstract class SocialNetworkPoster
{
    /**
     * The actual factory method. Note that it returns the abstract connector.
     * This lets subclasses return any concrete connectors without breaking the
     * superclass' contract.
     */
    abstract public function getSocialNetwork(): SocialNetworkConnector;

    /**
     * When the factory method is used inside the Creator's business logic, the
     * subclasses may alter the logic indirectly by returning different types of
     * the connector from the factory method.
     */
    public function post($content): void
    {
        // Call the factory method to create a Product object...
        $network = $this->getSocialNetwork();
        // ...then use it as you will
        $network->logIn();
        $network->createPost($content);
        $network->logout();
    }
}

/**
 * This Concrete Creator supports Facebook. Remember that this class also
 * inherits the 'post' method from the parent class. Concrete Creators are the
 * classes that the Client actually uses.
 */
class FacebookPoster extends SocialNetworkPoster
{
    private $login, $password;

    public function __construct(string $login, string $password)
    {
        $this->login = $login;
        $this->password = $password;
    }

    public function getSocialNetwork(): SocialNetworkConnector
    {
        return new FacebookConnector($this->login, $this->password);
    }
}

/**
 * This Concrete Creator supports LinkedIn.
 */
class LinkedInPoster extends SocialNetworkPoster
{
    private $email, $password;

    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    public function getSocialNetwork(): SocialNetworkConnector
    {
        return new LinkedInConnector($this->email, $this->password);
    }
}

/**
 * The Product interface declares behaviors of various types of products.
 */
interface SocialNetworkConnector
{
    public function logIn(): void;
    public function logOut(): void;
    public function createPost($content): void;
}

/**
 * This Concrete Product implements the Facebook API.
 */
class FacebookConnector implements SocialNetworkConnector
{
    private $login, $password;

    public function __construct(string $login, string $password)
    {
        $this->login = $login;
        $this->password = $password;
    }

    public function logIn(): void
    {
        echo nl2br("Send HTTP API request to log in user $this->login with " . "password $this->password\n");
    }

    public function logOut(): void
    {
        echo nl2br("Send HTTP API request to log out user $this->login\n");
    }

    public function createPost($content): void
    {
        echo nl2br("Send HTTP API request to create a post in Facebook timeline.\n");
    }
}

/**
 * This Concrete Product implements the LinkedIn API.
 */
class LinkedInConnector implements SocialNetworkConnector
{
    private $email, $password;

    public function __construct(string $email, string $password)
    {
        $this->email = $email;
        $this->password = $password;
    }

    public function logIn(): void
    {
        echo nl2br("Send HTTP API request to log in user $this->email with " . "password $this->password\n");
    }

    public function logOut(): void
    {
        echo nl2br("Send HTTP API request to log out user $this->email\n");
    }

    public function createPost($content): void
    {
        echo nl2br("Send HTTP API requests to create a post in LinkedIn timeline.\n");
    }
}

/**
 * The client code can work with any subclass of SocialNetworkPoster since it
 * doesn't depend on concrete classes.
 */
function clientCode(SocialNetworkPoster $creator)
{
    // ...
    $creator->post("Hello world!");
    $creator->post("I had a large cheeseburger this morning!");
    // ...
}

/**
 * During the initialization phase, the app can decide which social network it
 * wants to work with, create an object of the proper subclass, and pass it to
 * the client code.
 */
echo nl2br("Testing ConcreteCreator1:\n");
clientCode(new FacebookPoster("mile_kitic", "12345"));
echo nl2br("\n\n");

echo nl2br("Testing ConcreteCreator2:\n");
clientCode(new LinkedInPoster("mile_kitic@mail.com", "12345"));


/**
 * One of the best applications of the Builder pattern is an SQL query builder.
 * The builder interface defines the common steps required to build a generic SQL query.
 * On the other hand, concrete builders, corresponding to different SQL dialects,
 * implement these steps by returning parts of SQL queries that can be executed
 * in a particular database engine.
 */
namespace Builder\RealWorldExample;

/**
 * The Builder interface declares a set of methods to assemble an SQL query.
 *
 * All of the construction steps are returning the current builder object to
 * allow chaining: $builder->select(...)->where(...)
 */
interface SQLQueryBuilder
{
    public function select(string $table, array $fields): SQLQueryBuilder;
    public function where(string $field, string $value, string $operator = '=');
    public function limit(int $start, int $offset): SQLQueryBuilder;

    // +100 other SQL syntax methods
    public function getSQL(): string;
}

/**
 * Each Concrete Builder corresponds to a specific SQL dialect and may implement
 * the builder steps a little bit differently from the others.
 *
 * This Concrete Builder can build SQL queries compatible with MySQL.
 */
class MysqlQueryBuilder implements SQLQueryBuilder
{
    protected $query;

    protected function reset(): void
    {
        $this->query = new \stdClass();
    }

    /**
     * Build a base SELECT query.
     */
    public function select(string $table, array $fields): SQLQueryBuilder
    {
        $this->reset();
        $this->query->base = "SELECT " . implode(", ", $fields) . " FROM " . $table;
        $this->query->type = 'select';

        return $this;
    }

    /**
     * Add a WHERE condition.
     */
    public function where(string $field, string $value, string $operator = '=')
    {
        if (!in_array($this->query->type, ['select', 'update', 'delete'])) {
            throw new \Exception("WHERE can only be added to SELECT, UPDATE or DELETE");
        }
        $this->query->where[] = "$field $operator  '$value'";

        return $this;
    }

    /**
     * Add a LIMIT constraint.
     */
    public function limit(int $start, int $offset): SQLQueryBuilder
    {
        if (!in_array($this->query->type, ['select'])) {
            throw new \Exception("LIMIT can only be added to SELECT");
        }
        $this->query->limit = " LIMIT " . $start . ", " . $offset;

        return $this;
    }

    /**
     * Get the final query string.
     */
    public function getSQL(): string
    {
        $query = $this->query;
        $sql = $query->base;

        if (!empty($query->where)) {
            $sql .= " WHERE " . implode(' AND ', $query->where);
        }
        if (isset($query->limit)) {
            $sql .= $query->limit;
        }
        $sql .= ";";

        return $sql;
    }
}

/**
 * This Concrete Builder is compatible with PostgreSQL. While Postgres is very
 * similar to Mysql, it still has several differences. To reuse the common code,
 * we extend it from the MySQL builder, while overriding some of the building
 * steps.
 */

class PostgresQueryBuilder extends MysqlQueryBuilder
{
    /**
     * Among other things, PostgreSQL has slightly different LIMIT syntax.
     */
    public function limit(int $start, int $offset): SQLQueryBuilder
    {
        parent::limit($start, $offset);

        $this->query->limit = " LIMIT " . $start . " OFFSET " . $offset;

        return $this;
    }

// + tons of other overrides...
}

/**
 * Note that the client code uses the builder object directly. A designated
 * Director class is not necessary in this case, because the client code needs
 * different queries almost every time, so the sequence of the construction
 * steps cannot be easily reused.
 *
 * Since all our query builders create products of the same type (which is a
 * string), we can interact with all builders using their common interface.
 * Later, if we implement a new Builder class, we will be able to pass its
 * instance to the existing client code without breaking it thanks to the
 * SQLQueryBuilder interface.
 */
function clientCode(SQLQueryBuilder $queryBuilder)
{
    // ...

    $query = $queryBuilder
        ->select("users", ["name", "email", "password"])
        ->where("age", 18, ">")
        ->where("age", 30, "<")
        ->limit(10, 20)
        ->getSQL();

    echo $query;

    // ...
}

/**
 * The application selects the proper query builder type depending on a current
 * configuration or the environment settings.
 */
// if ($_ENV['database_type'] == 'postgres') {
//     $builder = new PostgresQueryBuilder(); } else {
//     $builder = new MysqlQueryBuilder(); }
//
// clientCode($builder);

echo nl2br("\n\n");

echo nl2br("Testing MySQL query builder:\n");
clientCode(new MysqlQueryBuilder());

echo nl2br("\n\n");

echo nl2br("Testing PostgresSQL query builder:\n");
clientCode(new PostgresQUeryBuilder());