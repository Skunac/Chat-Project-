import {Card, CardBody, CardFooter, CardHeader} from "@heroui/card";
import {Image} from "@heroui/image";
import {Divider} from "@heroui/divider";
import {useAuth} from "@/context/authContext";

export default function UserCard() {
    const auth = useAuth();
    return(
        <Card radius={"lg"} classNames={{ base: "bg-background/70" }}>
            <CardHeader>
                <Image
                    alt="User avatar"
                    height={100}
                    radius={"sm"}
                    src={auth.user?.avatarUrl ?? 'https://avatars.githubusercontent.com/u/86160567?s=200&v=4'}
                    width={100}
                />
                <div className="flex flex-col">
                    <p className="text-md">{auth.user?.email}</p>
                    <p className="text-small text-default-500">{auth.user?.displayName}</p>
                </div>
            </CardHeader>
            <Divider />
            <CardBody>
                <p>{auth.user?.lastSeen.toString()}</p>
            </CardBody>
        </Card>
    )
}