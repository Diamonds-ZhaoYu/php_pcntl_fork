#include <sys/time.h>  
#include <stdio.h>  
#include <sys/types.h>  
#include <sys/stat.h>  
#include <fcntl.h>  
#include <assert.h>  
 
int main (void)  
{  
    int keyboard;  
    int ret;
    int i;
    char c;  
    fd_set readfd;
    struct timeval timeout;  

    //打开键盘具柄
    keyboard = open("/dev/tty",O_RDONLY | O_NONBLOCK);  
    assert(keyboard > 0);

    while (1) {
        timeout.tv_sec = 1;
        timeout.tv_usec = 0;
        FD_ZERO(&readfd);  
        FD_SET(keyboard,&readfd);  
  
        ///监控函数  
        ret = select(keyboard+1,&readfd,NULL,NULL,&timeout);  
        if (ret == -1) {
            //错误情况
            printf("error!\n");  
        }
        else if (ret) {
            //返回值大于0 有数据到来
            if (FD_ISSET(keyboard,&readfd)) {

                i = read(keyboard,&c,1);

                if ('\n' == c)	continue;

                printf("the input is %c\n",c);  

                if ('q' == c)	break;
            }  
        }
        else {
        	//time out
            continue;  
        }  
    }  
}  
