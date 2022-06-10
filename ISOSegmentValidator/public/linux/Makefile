###
###     Makefile for ValidateMP4 encoder
###
###             generated for UNIX/LINUX environments
###



NAME=  ValidateMP4

### include debug information: 1=yes, 0=no
DBG=1
SAN=0
COV=0

FLAGS+=-Wall -Wextra -Werror

## TODO: remove these
FLAGS+=-Wno-unused-but-set-variable -Wno-unused-variable -Wno-unused-label -Wno-unused-parameter -Wno-int-to-pointer-cast -Wno-sequence-point -Wno-stringop-truncation

#compatibility=-static-libstdc++

DEPEND= dependencies

BINDIR= ./bin
INCDIR= ../src
SRCDIR= ../src
OBJDIR= ./obj


CC=     $(shell which g++)

FLAGS+=-std=c++11 -ffloat-store -Wall -I$(INCDIR) -DLITTLEENDIAN -Wno-multichar -Wno-unknown-pragmas -fpermissive -Wno-write-strings -fno-stack-protector

ifdef COV
LIBS= -lgcov #-lcurl
FLAGS+=-fprofile-generate -fprofile-arcs -ftest-coverage -Wl,-lgcov
LDFLAGS+=" -lgcov --coverage -WL -lgcov"
endif

ifdef SAN
FLAGS+=-fsanitize=address,undefined -fno-sanitize=vptr -g3
LDFLAGS+=-g
LIBS+=-fsanitize=address,undefined
endif

ifdef DBG
SUFFIX= #.dbg
FLAGS+= -g
else
SUFFIX=
FLAGS+= -O2 -DGCC
endif

ifdef COV
FLAGS+=-ftest-coverage -fprofile-arcs
endif

OBJSUF= .o$(SUFFIX)

SRC=    $(wildcard $(SRCDIR)/*.cpp) 
ADDSRC= $(wildcard $(ADDSRCDIR)/*.cpp)
OBJ=    $(SRC:$(SRCDIR)/%.cpp=$(OBJDIR)/%.o$(SUFFIX)) 
BIN=    $(BINDIR)/$(NAME)$(SUFFIX).exe


default: depend bin tags

dependencies:
	@echo "" >dependencies

clean:
	@echo remove all objects
	@rm -f $(OBJDIR)/*
	@rm -f $(SRCDIR)/*~
	@rm -f $(INCDIR)/*~
	@rm -f $(BIN)

tags:
	@echo update tag table
	@ctags $(INCDIR)/*.h $(SRCDIR)/*.cpp

bin:    $(OBJ)
	@echo
	@echo 'creating binary "$(BIN)"'
	@$(CC) $(compatibility) -o $(BIN) $(OBJ) $(LIBS)
	@echo '... done'
	@echo

depend:
	@echo
	@echo 'checking dependencies'
	@$(SHELL) -ec '$(CC) -MM $(CFLAGS) -I$(INCDIR) $(SRC) | sed '\''s@\(.*\)\.o[ :]@$(OBJDIR)/\1.o$(SUFFIX):@g'\'' >$(DEPEND)'
	@echo

$(OBJDIR)/%.o$(SUFFIX): $(SRCDIR)/%.cpp
	@echo 'compiling object file "$@" ...'
	@$(CC) $(compatibility) -c -o $@ $(FLAGS) $<

$(OBJDIR)/%.o$(SUFFIX): $(ADDSRCDIR)/%.c
	@echo 'compiling object file "$@" ...'
	@$(CC) $(compatibility) -c -o $@ $(FLAGS) $<

include $(DEPEND)


